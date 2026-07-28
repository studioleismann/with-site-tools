/**
 * With Site Tools webpack configuration.
 *
 * Extends the configuration shipped by @wordpress/scripts and discovers
 * JavaScript and SCSS entries below src/.
 */

const fs = require( 'fs' );
const path = require( 'path' );
const defaultConfigs = require( '@wordpress/scripts/config/webpack.config' );
const defaultConfig = Array.isArray( defaultConfigs )
	? defaultConfigs[ 0 ]
	: defaultConfigs;
const packageJson = require( './package.json' );
const pluginFile = path.resolve( __dirname, 'with-site-tools.php' );

function listSourceEntries( directory, base = directory ) {
	if ( ! fs.existsSync( directory ) ) {
		return {};
	}

	return fs
		.readdirSync( directory, { withFileTypes: true } )
		.reduce( ( entries, entry ) => {
			const absolutePath = path.join( directory, entry.name );

			if ( entry.isDirectory() ) {
				return {
					...entries,
					...listSourceEntries( absolutePath, base ),
				};
			}

			if ( ! /\.(js|scss)$/.test( entry.name ) ) {
				return entries;
			}

			if (
				path.extname( absolutePath ) === '.scss' &&
				fs.existsSync( absolutePath.replace( /\.scss$/, '.js' ) )
			) {
				return entries;
			}

			const relativePath = path
				.relative( base, absolutePath )
				.split( path.sep )
				.join( '/' );
			const name = relativePath.replace( /\.(js|scss)$/, '' );
			entries[ name ] = absolutePath;

			return entries;
		}, {} );
}

const entries = listSourceEntries( path.resolve( __dirname, 'src' ) );
const styleEntries = new Set(
	Object.entries( entries )
		.filter( ( [ , source ] ) => path.extname( source ) === '.scss' )
		.map( ( [ name ] ) => name )
);

/**
 * Keep compiled asset paths identical to their source paths.
 */
class NormalizeStyleAssetsPlugin {
	apply( compiler ) {
		compiler.hooks.thisCompilation.tap(
			'NormalizeStyleAssetsPlugin',
			( compilation ) => {
				compilation.hooks.processAssets.tap(
					{
						name: 'NormalizeStyleAssetsPlugin',
						stage: compiler.webpack.Compilation
							.PROCESS_ASSETS_STAGE_SUMMARIZE,
					},
					() => {
						for ( const entry of styleEntries ) {
							const javascript = `${ entry }.js`;
							const css = `${ entry }-style.css`;
							const rtlCss = `${ entry }-style-rtl.css`;

							if ( compilation.getAsset( javascript ) ) {
								compilation.deleteAsset( javascript );
							}
							if ( compilation.getAsset( css ) ) {
								compilation.renameAsset(
									css,
									`${ entry }.css`
								);
							}
							if ( compilation.getAsset( rtlCss ) ) {
								compilation.renameAsset(
									rtlCss,
									`${ entry }-rtl.css`
								);
							}
						}
					}
				);
			}
		);
	}
}

/**
 * Keep the WordPress plugin version synchronized with package.json.
 */
const updatePluginVersionPlugin = {
	apply( compiler ) {
		compiler.hooks.afterEmit.tap( 'UpdatePluginVersionPlugin', () => {
			const headerPattern = /(\* Version:\s+)[^\r\n]+/;
			const constantPattern =
				/(const WITH_SITE_TOOLS_VERSION\s*=\s*')[^']+(';)/;
			const source = fs.readFileSync( pluginFile, 'utf8' );

			if (
				! headerPattern.test( source ) ||
				! constantPattern.test( source )
			) {
				throw new Error(
					'Could not find both WordPress plugin version declarations.'
				);
			}

			const updated = source
				.replace( headerPattern, `$1${ packageJson.version }` )
				.replace( constantPattern, `$1${ packageJson.version }$2` );

			if ( updated !== source ) {
				fs.writeFileSync( pluginFile, updated, 'utf8' );
			}
		} );
	},
};

module.exports = {
	...defaultConfig,
	entry: entries,
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
		filename: '[name].js',
		clean: true,
	},
	performance: {
		hints: false,
	},
	plugins: [
		...defaultConfig.plugins,
		new NormalizeStyleAssetsPlugin(),
		updatePluginVersionPlugin,
	],
};
