const FileSystem = require('fs');
const Path = require('path');
const UglifyJS = require('uglify-js');
const { minify: HtmlMinify } = require('html-minifier-terser');
const postcss = require('postcss');
const cssnano = require('cssnano');
const atImport = require('postcss-import');
const crypto = require('crypto');
const { optimize: svgOptimizer } = require('svgo');
const OptiPng = require('optipng');

const distPath = Path.join(__dirname, '/../dist');
const srcPath = Path.join(__dirname, '/../src');

async function minifyCss(sourcePath, importPath) {
  const sourceCode = await FileSystem.promises.readFile(sourcePath, 'utf8');
  return await postcss([atImport({ path: [importPath] }), cssnano])
    .process(sourceCode, {
      from: undefined
    })
    .then(result => {
      return result.css;
    });
}

async function minifyJs(sourcePath) {
  const sourceCode = await FileSystem.promises.readFile(sourcePath, 'utf8');
  return UglifyJS.minify(sourceCode, {
    compress: {
      drop_console: false,
      pure_funcs: [
        // Exclude "console.log" statements.
        "console.log",
      ]
    },
    mangle: true,
    output: {
      // Preserve compulsory comments (eg. version name).
      comments: '/^!/',
    }
  }).code;
}

async function minifyHtml(sourcePath) {
  const sourceCode = await FileSystem.promises.readFile(sourcePath, 'utf8');
  return await HtmlMinify(sourceCode, {
    removeAttributeQuotes: true,
    collapseWhitespace: true,
    collapseInlineTagWhitespace: false,
    removeComments: true
  });
}

function generateHashFromCode(code) {
  // Calculate the SHA256 hash of the script code
  const hash = crypto.createHash('sha256').update(code).digest();
  // Take the first 32 bytes of the hash as the nonce value
  return hash.subarray(0, 32).toString('base64');
}

function copyFile(source, target) {
  const readStream = FileSystem.createReadStream(source);
  const writeStream = FileSystem.createWriteStream(target);
  readStream.pipe(writeStream);
}

function copyFileWithContentsChange(source, target, search, replacement) {
  let contents = FileSystem.readFileSync(source, 'utf8');
  search.forEach((substring, index) => {
    contents = contents.replace(substring, replacement[index]);
  });
  FileSystem.writeFileSync(target, contents);
}

function readDirRecursive(path, callback, callDirs = false, selfFirst = true) {
  // Read the contents of the directory
  const files = FileSystem.readdirSync(path);
  // Loop through each file
  for (const file of files) {
    const filePath = Path.join(path, file);
    // Check if the file is a directory
    if (FileSystem.statSync(filePath).isDirectory()) {
      if (callDirs && selfFirst) {
        callback(filePath);
      }
      // Recursively loop through the subdirectory
      readDirRecursive(filePath, callback, callDirs, selfFirst);
      if (callDirs && !selfFirst) {
        callback(filePath);
      }
    } else {
      callback(filePath);
    }
  }
}

function emptyDir(path) {
  const files = FileSystem.readdirSync(path);
  for (const file of files) {
    FileSystem.rmSync(
      Path.join(path, file), {
        recursive: true,
        force: true
      }
    );
  }
}

function copyDirContents(path, dest, fileCallback) {
  readDirRecursive(path, filePath => {
    const relative = Path.relative(path, filePath);
    const destPath = Path.join(dest, relative);
    if (FileSystem.statSync(filePath).isDirectory()) {
      FileSystem.mkdirSync(destPath, {
          recursive: true
      });
    } else {
      const copyInst = copyFile.bind(this, filePath, destPath);
      if (typeof fileCallback === 'function') {
        fileCallback(filePath, destPath, copyInst);
      } else {
        copyInst();
      }
    }
  }, true, true);
}

async function transferMinified(filePath, destPath, defaultAction) {
  const extension = Path.extname(filePath);
  switch (extension) {
    case '.css': {
      const minifiedCssCode = await minifyCss(
        filePath,
        Path.dirname(filePath)
      );
      FileSystem.writeFileSync(destPath, minifiedCssCode);
      break;
    }
    case '.js': {
      const minifiedJsCode = await minifyJs(filePath);
      FileSystem.writeFileSync(destPath, minifiedJsCode);
      break;
    }
    case '.png': {
      const sourceStream = FileSystem.createReadStream(filePath);
      const destinationStream = FileSystem.createWriteStream(destPath);
      // Create an OptiPng instance with optimization level 7
      const myOptimizer = new OptiPng(['-o7']);
      // Pipe the input stream through the OptiPng instance to the output stream
      sourceStream.pipe(myOptimizer).pipe(destinationStream);
      break;
    }
    case '.svg': {
      const result = svgOptimizer(FileSystem.readFileSync(filePath, 'utf8'), {
        multipass: true,
      });
      FileSystem.writeFileSync(destPath, result.data);
      break;
    }
    default:
      defaultAction();
      break;
  }
}

// Empty dist directory.
if (FileSystem.existsSync(distPath)) {
  emptyDir(distPath);
}

function makeDirInDist(relativePath) {
  FileSystem.mkdirSync(
    Path.join(distPath, relativePath), {
      recursive: true
    }
  );
}

makeDirInDist('stonetable/lib');
makeDirInDist('web/stonetable/app/assets/images');
makeDirInDist('web/stonetable/api');
makeDirInDist('web/stonetable/assets');

copyFile(
  Path.join(srcPath, 'web/app/manifest.webmanifest'),
  Path.join(distPath, 'web/stonetable/app/manifest.webmanifest')
);

const minifyAppCss = minifyCss(
  Path.join(srcPath, 'web/app/assets/css/app.css'),
  Path.join(srcPath, 'web/app/assets/css'),
)

const minifyAppJs = minifyJs(
  Path.join(srcPath, 'web/app/assets/scripts/app.js'),
)

const minifyAppHtml = minifyHtml(
  Path.join(srcPath, 'web/app/index.html')
)

Promise.all([
  minifyAppHtml,
  minifyAppCss,
  minifyAppJs
]).then(([htmlCode, cssCode, jsCode]) => {
  FileSystem.writeFileSync(
    Path.join(distPath, 'web/stonetable/app/index.html'),
    htmlCode.replace(
      `<link href=assets/css/app.css rel=stylesheet>`,
      `<style>${cssCode}</style>`
    ).replace(
      `<script src=assets/scripts/app.js></script>`,
      `<script>${jsCode}</script>`
    ).replace(
      `<meta http-equiv=Content-Security-Policy content="default-src 'self';`
      + ` connect-src *">`,
      `<meta http-equiv=Content-Security-Policy content="default-src 'self';`
      + ` connect-src *; style-src 'sha256-${generateHashFromCode(cssCode)}';`
      + ` script-src 'sha256-${generateHashFromCode(jsCode)}'">`,
    )
  );
});

copyDirContents(
  Path.join(srcPath, 'web/app/assets/images'),
  Path.join(distPath, 'web/stonetable/app/assets/images'),
  transferMinified
)

copyDirContents(
  Path.join(srcPath, 'web/api'),
  Path.join(distPath, 'web/stonetable/api')
)

copyDirContents(
  Path.join(srcPath, 'web/assets'),
  Path.join(distPath, 'web/stonetable/assets'),
  transferMinified
)

copyFileWithContentsChange(
  Path.join(srcPath, 'web/config.php'),
  Path.join(distPath, 'web/stonetable/config.php'),
  [`const LIB_PATH = (__DIR__ . '/../lib');`],
  [`const LIB_PATH = (__DIR__ . '/../../stonetable/lib');`]
)

copyFile(
  Path.join(srcPath, 'web/demo-page-init.php'),
  Path.join(distPath, 'web/stonetable/demo-page-init.php')
)

copyFile(
  Path.join(srcPath, 'web/utilities.php'),
  Path.join(distPath, 'web/stonetable/utilities.php')
)

copyDirContents(
  Path.join(srcPath, 'lib'),
  Path.join(distPath, 'stonetable/lib'),
  transferMinified
)