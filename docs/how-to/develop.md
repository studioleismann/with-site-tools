# Develop the plugin locally

Use this guide to install development dependencies, run WordPress locally, and
validate a change.

## Install dependencies

From the repository root, run:

```bash
npm ci
```

Node.js 20 or newer is required.

## Start the development environment

```bash
npm run env:start
```

`wp-env` mounts and activates the repository root as a plugin and selects an
available port. Follow the URLs printed by the command.

For a continuously rebuilt development bundle, run this in a second terminal:

```bash
npm start
```

## Validate a change

```bash
npm run check
```

This creates the production build and runs JavaScript, CSS, WordPress Coding
Standards, PHP syntax, and version checks. Generated files in `build/` are
release files and must be committed when they change.

Stop the environment when finished:

```bash
npm run env:stop
```

Use `npm run env:clean` only when you intentionally want to remove the local
`wp-env` data.
