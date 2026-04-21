import { build } from 'esbuild';
import { cp, mkdir, readdir, readFile, rm, stat, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const rootDir = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const nodeModulesDir = path.join(rootDir, 'node_modules');
const vendorDir = path.join(rootDir, 'vendor');
const fontDir = path.join(rootDir, 'font');

const packages = [
  {
    name: 'bpmn-js',
    globalName: 'BpmnJS',
    assetsDir: path.join('dist', 'assets'),
    fontsDir: path.join('dist', 'assets', 'bpmn-font', 'font'),
    fontFiles: ['bpmn.woff', 'bpmn.woff2'],
    outputs: [
      {
        entryPoint: path.join(rootDir, 'build', 'vendor-entrypoints', 'bpmn-viewer.js'),
        outFile: path.join(vendorDir, 'bpmn-js', 'dist', 'bpmn-viewer.production.min.js'),
      },
      {
        entryPoint: path.join(rootDir, 'build', 'vendor-entrypoints', 'bpmn-modeler.js'),
        outFile: path.join(vendorDir, 'bpmn-js', 'dist', 'bpmn-modeler.production.min.js'),
      },
    ],
  },
  {
    name: 'dmn-js',
    globalName: 'DmnJS',
    assetsDir: path.join('dist', 'assets'),
    fontsDir: path.join('dist', 'assets', 'dmn-font', 'font'),
    fontFiles: ['dmn.woff', 'dmn.woff2'],
    outputs: [
      {
        entryPoint: path.join(rootDir, 'build', 'vendor-entrypoints', 'dmn-viewer.js'),
        outFile: path.join(vendorDir, 'dmn-js', 'dist', 'dmn-viewer.production.min.js'),
      },
      {
        entryPoint: path.join(rootDir, 'build', 'vendor-entrypoints', 'dmn-modeler.js'),
        outFile: path.join(vendorDir, 'dmn-js', 'dist', 'dmn-modeler.production.min.js'),
      },
    ],
  },
];

async function pathExists(targetPath) {
  try {
    await stat(targetPath);
    return true;
  } catch {
    return false;
  }
}

async function ensureInstalled(packageName) {
  const packagePath = path.join(nodeModulesDir, packageName, 'package.json');

  if (!(await pathExists(packagePath))) {
    throw new Error(
      `Missing npm dependency ${packageName}. Run npm install before building vendor bundles.`
    );
  }

  return packagePath;
}

async function readPackageMetadata(packageName) {
  const packagePath = await ensureInstalled(packageName);
  return JSON.parse(await readFile(packagePath, 'utf8'));
}

function createBanner(metadata) {
  return `/*! ${metadata.name} - ${metadata.version} | generated for dokuwiki-plugin-bpmnio | ${metadata.license} */`;
}

async function copyFileEnsuringDir(sourcePath, targetPath) {
  await mkdir(path.dirname(targetPath), { recursive: true });
  await cp(sourcePath, targetPath, { force: true });
}

async function copyAssets(sourceDir, targetDir) {
  await mkdir(targetDir, { recursive: true });

  for (const entry of await readdir(sourceDir, { withFileTypes: true })) {
    const sourcePath = path.join(sourceDir, entry.name);
    const normalizedPath = sourcePath.split(path.sep).join('/');

    if (normalizedPath.includes('/font/')) {
      continue;
    }

    if (entry.isDirectory()) {
      await copyAssets(sourcePath, path.join(targetDir, entry.name));
      continue;
    }

    const targetName = entry.name.endsWith('.css')
      ? entry.name.replace(/\.css$/u, '.less')
      : entry.name;

    await copyFileEnsuringDir(sourcePath, path.join(targetDir, targetName));
  }
}

async function copyFonts(sourceDir, files) {
  await mkdir(fontDir, { recursive: true });

  for (const file of files) {
    await copyFileEnsuringDir(path.join(sourceDir, file), path.join(fontDir, file));
  }
}

async function cleanPackageOutput(packageName) {
  await rm(path.join(vendorDir, packageName), { recursive: true, force: true });
}

async function copyMetadata(packageName) {
  const sourceDir = path.join(nodeModulesDir, packageName);
  const targetDir = path.join(vendorDir, packageName);

  for (const file of ['LICENSE', 'README.md', 'package.json']) {
    const sourcePath = path.join(sourceDir, file);
    if (await pathExists(sourcePath)) {
      await copyFileEnsuringDir(sourcePath, path.join(targetDir, file));
    }
  }
}

async function buildBundle({ entryPoint, outFile, metadata, packageName }) {
  await mkdir(path.dirname(outFile), { recursive: true });

  await build({
    entryPoints: [entryPoint],
    outfile: outFile,
    bundle: true,
    minify: true,
    platform: 'browser',
    format: 'iife',
    target: ['es2019'],
    legalComments: 'inline',
    banner: {
      js: createBanner(metadata),
    },
    define: {
      'process.env.NODE_ENV': '"production"',
      global: 'window',
    },
    logLevel: 'info',
  });

  console.log(`Built ${packageName} bundle: ${path.relative(rootDir, outFile)}`);
}

async function main() {
  for (const pkg of packages) {
    const metadata = await readPackageMetadata(pkg.name);
    const sourceDir = path.join(nodeModulesDir, pkg.name);
    const sourceAssetsDir = path.join(sourceDir, pkg.assetsDir);
    const sourceFontsDir = path.join(sourceDir, pkg.fontsDir);
    const targetAssetsDir = path.join(vendorDir, pkg.name, pkg.assetsDir);

    await cleanPackageOutput(pkg.name);
    await copyMetadata(pkg.name);
    await copyAssets(sourceAssetsDir, targetAssetsDir);
    await copyFonts(sourceFontsDir, pkg.fontFiles);

    for (const output of pkg.outputs) {
      await buildBundle({
        entryPoint: output.entryPoint,
        outFile: output.outFile,
        metadata,
        packageName: pkg.name,
      });
    }
  }

  const generatedMetadata = {
    generatedAt: new Date().toISOString(),
    packages: Object.fromEntries(
      await Promise.all(
        packages.map(async (pkg) => [pkg.name, (await readPackageMetadata(pkg.name)).version])
      )
    ),
  };

  await writeFile(
    path.join(vendorDir, 'build-manifest.json'),
    `${JSON.stringify(generatedMetadata, null, 2)}\n`
  );
}

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
