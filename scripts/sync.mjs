#!/usr/bin/env node

import { createHash } from 'node:crypto';
import { existsSync } from 'node:fs';
import {
	copyFile,
	mkdir,
	readFile,
	readdir,
	rm,
	rmdir,
	stat,
	writeFile,
} from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const PACKAGE_ROOT = path.resolve(
	path.dirname( fileURLToPath( import.meta.url ) ),
	'..'
);
const PLUGIN_DIRECTORY = 'with-site-tools';
const MANIFEST_FILE = '.with-site-tools-manifest.json';
const RUNTIME_PATHS = [
	'with-site-tools.php',
	'uninstall.php',
	'build',
	'docs',
	'includes',
	'src',
	'README.md',
	'CHANGELOG.md',
	'LICENSE',
];

function printHelp() {
	process.stdout.write( `with-site-tools

Usage:
  with-site-tools sync [--dry-run] [--target <path>]
  with-site-tools help

The target must be the final wp-content/plugins/with-site-tools directory.
When run from a WordPress root or a theme root, it is detected automatically.
` );
}

function parseArguments( argumentsList ) {
	const [ command = 'help', ...flags ] = argumentsList;
	let target = '';

	for ( let index = 0; index < flags.length; index += 1 ) {
		const flag = flags[ index ];

		if ( flag === '--target' ) {
			target = flags[ index + 1 ] || '';
			index += 1;
		} else if ( flag.startsWith( '--target=' ) ) {
			target = flag.slice( '--target='.length );
		}
	}

	return {
		command,
		dryRun: flags.includes( '--dry-run' ),
		target,
	};
}

function resolveTarget( explicitTarget ) {
	if ( explicitTarget ) {
		return path.resolve( process.cwd(), explicitTarget );
	}

	const wpRootTarget = path.join(
		process.cwd(),
		'wp-content',
		'plugins',
		PLUGIN_DIRECTORY
	);
	if ( existsSync( path.dirname( wpRootTarget ) ) ) {
		return wpRootTarget;
	}

	if (
		path.basename( path.dirname( process.cwd() ) ) === 'themes' &&
		path.basename( path.dirname( path.dirname( process.cwd() ) ) ) ===
			'wp-content'
	) {
		return path.join(
			path.dirname( path.dirname( process.cwd() ) ),
			'plugins',
			PLUGIN_DIRECTORY
		);
	}

	throw new Error(
		'Could not detect wp-content/plugins. Pass --target with the final plugin directory.'
	);
}

function hashContent( content ) {
	return createHash( 'sha256' ).update( content ).digest( 'hex' );
}

async function fileHash( filePath ) {
	return hashContent( await readFile( filePath ) );
}

async function listFiles( directory, base = directory ) {
	const entries = await readdir( directory, { withFileTypes: true } );
	const files = [];

	for ( const entry of entries ) {
		const absolutePath = path.join( directory, entry.name );

		if ( entry.isDirectory() ) {
			files.push( ...( await listFiles( absolutePath, base ) ) );
		} else if ( entry.isFile() ) {
			files.push( path.relative( base, absolutePath ) );
		}
	}

	return files;
}

async function listRuntimeFiles() {
	const files = [];

	for ( const runtimePath of RUNTIME_PATHS ) {
		const absolutePath = path.join( PACKAGE_ROOT, runtimePath );

		if ( ! existsSync( absolutePath ) ) {
			if ( runtimePath === 'build' ) {
				throw new Error(
					'The build directory is missing. Run npm run build before syncing.'
				);
			}
			continue;
		}

		const runtimeStat = await stat( absolutePath );

		if ( runtimeStat.isDirectory() ) {
			const directoryFiles = await listFiles( absolutePath );
			files.push(
				...directoryFiles
					.filter(
						( file ) =>
							runtimePath !== 'src' ||
							[ '.php', '.svg' ].includes( path.extname( file ) )
					)
					.map( ( file ) => path.join( runtimePath, file ) )
			);
		} else {
			files.push( runtimePath );
		}
	}

	return files.sort();
}

async function readManifest( target ) {
	const manifestPath = path.join( target, MANIFEST_FILE );

	if ( ! existsSync( manifestPath ) ) {
		return { version: 1, files: {} };
	}

	try {
		const manifest = JSON.parse( await readFile( manifestPath, 'utf8' ) );

		return {
			version: 1,
			files:
				manifest && typeof manifest.files === 'object'
					? manifest.files
					: {},
		};
	} catch ( error ) {
		throw new Error(
			`Could not read ${ manifestPath }: ${ error.message }`
		);
	}
}

async function removeEmptyParentDirectories( directory, targetRoot ) {
	let currentDirectory = directory;

	while (
		currentDirectory !== targetRoot &&
		currentDirectory.startsWith( `${ targetRoot }${ path.sep }` ) &&
		existsSync( currentDirectory ) &&
		( await readdir( currentDirectory ) ).length === 0
	) {
		await rmdir( currentDirectory );
		currentDirectory = path.dirname( currentDirectory );
	}
}

async function sync( { target, dryRun } ) {
	const resolvedTarget = path.resolve( target );
	const resolvedPackage = path.resolve( PACKAGE_ROOT );

	if ( resolvedTarget === resolvedPackage ) {
		throw new Error(
			'The sync target cannot be the package source directory.'
		);
	}

	if (
		path.basename( resolvedTarget ) !== PLUGIN_DIRECTORY ||
		path.basename( path.dirname( resolvedTarget ) ) !== 'plugins'
	) {
		throw new Error(
			`The sync target must be the final wp-content/plugins/${ PLUGIN_DIRECTORY } directory.`
		);
	}

	const files = await listRuntimeFiles();
	const sourceFiles = new Set( files );
	const manifest = await readManifest( resolvedTarget );
	const nextManifest = { version: 1, files: { ...manifest.files } };
	const operations = [];
	const summary = {
		created: 0,
		updated: 0,
		removed: 0,
		unchanged: 0,
		conflicts: [],
	};

	for ( const relativeFile of files ) {
		const sourcePath = path.join( PACKAGE_ROOT, relativeFile );
		const targetPath = path.resolve( resolvedTarget, relativeFile );

		if ( ! targetPath.startsWith( `${ resolvedTarget }${ path.sep }` ) ) {
			throw new Error( `Unsafe runtime path: ${ relativeFile }` );
		}

		const sourceHash = await fileHash( sourcePath );
		const targetExists = existsSync( targetPath );
		const targetHash = targetExists ? await fileHash( targetPath ) : null;
		const previous = manifest.files[ relativeFile ];

		if ( targetHash === sourceHash ) {
			summary.unchanged += 1;
			nextManifest.files[ relativeFile ] = { sourceHash, targetHash };
			continue;
		}

		if (
			targetExists &&
			( ! previous || previous.targetHash !== targetHash )
		) {
			summary.conflicts.push( relativeFile );
			continue;
		}

		operations.push( { action: 'copy', sourcePath, targetPath } );
		summary[ targetExists ? 'updated' : 'created' ] += 1;
		nextManifest.files[ relativeFile ] = {
			sourceHash,
			targetHash: sourceHash,
		};
	}

	for ( const [ relativeFile, previous ] of Object.entries(
		manifest.files
	) ) {
		if ( sourceFiles.has( relativeFile ) ) {
			continue;
		}

		const targetPath = path.resolve( resolvedTarget, relativeFile );

		if (
			! targetPath.startsWith( `${ resolvedTarget }${ path.sep }` ) ||
			! existsSync( targetPath )
		) {
			delete nextManifest.files[ relativeFile ];
			continue;
		}

		const targetHash = await fileHash( targetPath );
		if ( previous.targetHash !== targetHash ) {
			summary.conflicts.push( relativeFile );
			continue;
		}

		operations.push( { action: 'remove', targetPath } );
		delete nextManifest.files[ relativeFile ];
		summary.removed += 1;
	}

	if ( summary.conflicts.length > 0 ) {
		throw new Error(
			`Sync stopped because locally changed files would be overwritten:\n${ summary.conflicts
				.map( ( file ) => `- ${ file }` )
				.join( '\n' ) }`
		);
	}

	if ( ! dryRun ) {
		for ( const operation of operations ) {
			if ( operation.action === 'copy' ) {
				await mkdir( path.dirname( operation.targetPath ), {
					recursive: true,
				} );
				await copyFile( operation.sourcePath, operation.targetPath );
			} else {
				await rm( operation.targetPath );
				await removeEmptyParentDirectories(
					path.dirname( operation.targetPath ),
					resolvedTarget
				);
			}
		}

		await mkdir( resolvedTarget, { recursive: true } );
		await writeFile(
			path.join( resolvedTarget, MANIFEST_FILE ),
			`${ JSON.stringify( nextManifest, null, '\t' ) }\n`
		);
	}

	process.stdout.write(
		`${ dryRun ? 'Dry run: ' : '' }${ summary.created } created, ${
			summary.updated
		} updated, ${ summary.removed } removed, ${
			summary.unchanged
		} unchanged.\n`
	);
	process.stdout.write( `Target: ${ resolvedTarget }\n` );
}

const options = parseArguments( process.argv.slice( 2 ) );

try {
	if ( options.command === 'help' || options.command === '--help' ) {
		printHelp();
	} else if ( options.command === 'sync' ) {
		await sync( {
			target: resolveTarget( options.target ),
			dryRun: options.dryRun,
		} );
	} else {
		printHelp();
		process.exitCode = 1;
	}
} catch ( error ) {
	process.stderr.write(
		`Error: ${ error instanceof Error ? error.message : String( error ) }\n`
	);
	process.exitCode = 1;
}
