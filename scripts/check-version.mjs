#!/usr/bin/env node

import { readFile } from 'node:fs/promises';

const packageJson = JSON.parse(
	await readFile( new URL( '../package.json', import.meta.url ), 'utf8' )
);
const pluginSource = await readFile(
	new URL( '../with-site-tools.php', import.meta.url ),
	'utf8'
);
const requestedVersion = ( process.argv[ 2 ] || packageJson.version ).replace(
	/^v/,
	''
);
const headerVersion = pluginSource
	.match( /^ \* Version:\s+(.+)$/m )?.[ 1 ]
	?.trim();
const constantVersion = pluginSource.match(
	/const WITH_SITE_TOOLS_VERSION\s*=\s*'([^']+)'/
)?.[ 1 ];

for ( const [ label, version ] of Object.entries( {
	'package.json': packageJson.version,
	'plugin header': headerVersion,
	'plugin constant': constantVersion,
} ) ) {
	if ( version !== requestedVersion ) {
		throw new Error(
			`${ label } version ${
				version || '(missing)'
			} does not match ${ requestedVersion }.`
		);
	}
}

process.stdout.write(
	`With Site Tools version ${ requestedVersion } is synchronized.\n`
);
