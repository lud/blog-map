'use strict';

function _interopDefault (ex) { return (ex && (typeof ex === 'object') && 'default' in ex) ? ex['default'] : ex; }

var fs = _interopDefault(require('fs'));
var path = _interopDefault(require('path'));
var relative = _interopDefault(require('require-relative'));
var svelte = require('svelte');
var rollupPluginutils = require('rollup-pluginutils');
var sourcemapCodec = require('sourcemap-codec');

function sanitize(input) {
    return path
        .basename(input)
        .replace(path.extname(input), '')
        .replace(/[^a-zA-Z_$0-9]+/g, '_')
        .replace(/^_/, '')
        .replace(/_$/, '')
        .replace(/^(\d)/, '_$1');
}

function capitalize(str) {
    return str[0].toUpperCase() + str.slice(1);
}

const pluginOptions = {
    include: true,
    exclude: true,
    extensions: true,
    shared: true
};

function tryRequire(id) {
    try {
        return require(id);
    } catch (err) {
        return null;
    }
}

function tryResolve(pkg, importer) {
    try {
        return relative.resolve(pkg, importer);
    } catch (err) {
        if (err.code === 'MODULE_NOT_FOUND') return null;
        throw err;
    }
}

function exists(file) {
    try {
        fs.statSync(file);
        return true;
    } catch (err) {
        if (err.code === 'ENOENT') return false;
        throw err;
    }
}

function mkdirp(dir) {
    const parent = path.dirname(dir);
    if (parent === dir) return;

    mkdirp(parent);

    try {
        fs.mkdirSync(dir);
    } catch (err) {
        if (err.code !== 'EEXIST') throw err;
    }
}

class CssWriter {
    constructor (code, map) {
        this.code = code;
        this.map = {
            version: 3,
            file: null,
            sources: map.sources,
            sourcesContent: map.sourcesContent,
            names: [],
            mappings: map.mappings
        };
    }

    write(dest, map) {
        dest = path.resolve(dest);
        mkdirp(path.dirname(dest));

        const basename = path.basename(dest);

        if (map !== false) {
            fs.writeFileSync(dest, `${this.code}\n/*# sourceMappingURL=${basename}.map */`);
            fs.writeFileSync(`${dest}.map`, JSON.stringify({
                version: 3,
                file: basename,
                sources: this.map.sources.map(source => path.relative(path.dirname(dest), source)),
                sourcesContent: this.map.sourcesContent,
                names: [],
                mappings: this.map.mappings
            }, null, '  '));
        } else {
            fs.writeFileSync(dest, this.code);
        }
    }

    toString() {
        return this.code;
    }
}

function svelte$1(options = {}) {
    const filter = rollupPluginutils.createFilter(options.include, options.exclude);

    const extensions = options.extensions || ['.html', '.svelte'];

    const fixedOptions = {};

    Object.keys(options).forEach(key => {
        // add all options except include, exclude, extensions, and shared
        if (pluginOptions[key]) return;
        fixedOptions[key] = options[key];
    });

    fixedOptions.format = 'es';
    fixedOptions.shared = require.resolve(options.shared || 'svelte/shared.js');

    // handle CSS extraction
    if ('css' in options) {
        if (typeof options.css !== 'function' && typeof options.css !== 'boolean') {
            throw new Error('options.css must be a boolean or a function');
        }
    }

    let css = options.css && typeof options.css === 'function'
        ? options.css
        : null;

    const cssLookup = new Map();
    const ffff = cssLookup.get
    cssLookup.get = function(key){
        return ffff.apply(cssLookup, arguments)
    }

    if (css || options.emitCss) {
        fixedOptions.css = false;
    }

    if (options.onwarn) {
        fixedOptions.onwarn = options.onwarn;
    }

    return {
        name: 'svelte',

        load(id) {
            if (!cssLookup.has(id)) return null;
            return cssLookup.get(id);
        },

        resolveId(importee, importer) {
            if (cssLookup.has(importee)) { return importee; }
            if (!importer || importee[0] === '.' || importee[0] === '\0' || path.isAbsolute(importee))
                return null;

            // if this is a bare import, see if there's a valid pkg.svelte
            const parts = importee.split('/');
            let name = parts.shift();
            if (name[0] === '@') name += `/${parts.shift()}`;

            const resolved = tryResolve(
                `${name}/package.json`,
                path.dirname(importer)
            );
            if (!resolved) return null;
            const pkg = tryRequire(resolved);
            if (!pkg) return null;

            const dir = path.dirname(resolved);

            if (parts.length === 0) {
                // use pkg.svelte
                if (pkg.svelte) {
                    return path.resolve(dir, pkg.svelte);
                }
            } else {
                if (pkg['svelte.root']) {
                    // TODO remove this. it's weird and unnecessary
                    const sub = path.resolve(dir, pkg['svelte.root'], parts.join('/'));
                    if (exists(sub)) return sub;
                }
            }
        },

        transform(code, id) {
            if (!filter(id)) return null;
            if (!~extensions.indexOf(path.extname(id))) return null;

            return (options.preprocess ? svelte.preprocess(code, Object.assign({}, options.preprocess, { filename : id })) : Promise.resolve(code)).then(code => {
                const compiled = svelte.compile(
                    code.toString(),
                    Object.assign({}, {
                        onwarn: warning => {
                            if ((options.css || !options.emitCss) && warning.code === 'css-unused-selector') return;
                            this.warn(warning);
                        },
                        onerror: error => this.error(error)
                    }, fixedOptions, {
                        name: capitalize(sanitize(id)),
                        filename: id
                    })
                );

                if ((css || options.emitCss) && compiled.css.code) {


                    let fname = id.replace('.html', '.css');


                    if (options.emitCss) {
                        const source_map_comment = `/*# sourceMappingURL=${compiled.css.map.toUrl()} */`;
                        compiled.css.code += `\n${source_map_comment}`;
                        compiled.js.code += `\nimport '${fname.replace(/\\/g, '\\\\' )}';\n`;
                    }

                    cssLookup.set(fname, compiled.css);
                }

                return compiled.js;
            });
        },
        ongenerate() {
            if (css) {
                // write out CSS file. TODO would be nice if there was a
                // a more idiomatic way to do this in Rollup
                let result = '';

                const mappings = [];
                const sources = [];
                const sourcesContent = [];

                for (let chunk of cssLookup.values()) {
                    if (!chunk.code) continue;
                    result += chunk.code + '\n';

                    if (chunk.map) {
                        const i = sources.length;
                        sources.push(chunk.map.sources[0]);
                        sourcesContent.push(chunk.map.sourcesContent[0]);

                        const decoded = chunk.map.mappings;

                        if (i > 0) {
                            decoded.forEach(line => {
                                line.forEach(segment => {
                                    segment[1] = i;
                                });
                            });
                        }

                        mappings.push(...decoded);
                    }
                }

                const encodedMappings = sourcemapCodec.encode(mappings)


                const writerMeta = {
                    sources,
                    sourcesContent,
                    mappings: encodedMappings
                }

                const writer = new CssWriter(result, {
                    sources,
                    sourcesContent,
                    mappings: sourcemapCodec.encode(mappings)
                });


                css(writer);
            }
        }
    };
}

module.exports = svelte$1;
