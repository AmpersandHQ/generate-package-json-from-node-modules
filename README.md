# generate-package-json-from-node-modules
A script to generate `package.json` dependencies from an existing `node_modules` directory

## Use case

- You have a project with a `node_modules` directory. 
- There is either no `package-lock.json` or it is out of sync with the actual versions installed (perhaps `npm install` was used instead of `npm ci`)
- You want to make sure you can build the exact same `node_modules` at the exact same versions.
- `npm init` is not working this out for you automatically. (Perhaps some meta data has been stripped breaking that functionality?)

## How to use

```bash
$ php generate.php /path/to/node_modules
``` 

This will output a json string of dependencies and exact versions, copy this into your existing `package.json`.  It is not perfect to specify exact versions in a `package.json` but at least we can verify we are building the same `node_modules` directory.

It is possible this script has generated some package names which aren't actually available for install directly. For example `bs-recipes-server` seems to be part of `bs-recipes`. We need to remove these packages from `package.json` for `npm install` to work.

```
$ php remove-invalid-packages.php /Users/ampersand/src/project/package.json
Working directory: /Users/ampersand/src/project
npm install 2>&1
Trying to remove bs-recipes-server ... SUCCESS
npm install 2>&1
audited 1769 packages in 11.783s
DONE
```

Once you have `npm install` working you will have a `package-lock.json` which is valid and locked. You can probably remove the overly verbose items from `package.json` and use `npm ci` to build the project from this point onwards. 

```
rm -rf node_modules
rm package-lock.json
npm install
# You have a valid package-lock.json! Tidy up your package.json file and use `npm ci` from this point forward
```

