# Widen CLI

This is a command-line tool that generates a CSV with of a list of assets from the Widen API that have both the Webdam Resource ID `webdam_id` and Widen Resource ID `widen_id`.

## Installation

1. Clone this repository
   ```shell
   $ git clone git@github.com:amansrivastava/widen-cli.git
   ```
2. Install the composer dependencies
   ```shell
   $ cd widen-cli
   $ composer install
   ```

## Usage:
Note: If you want to run the below commands in a different directory than `widen-cli/`, replace `./widen-cli` with the path to the `widen-cli` executable (e.g., `/Users/jane.doe/Sandboxes/widen-cli/widen-cli export:csv`)

### Export CSV:
```shell
$ ./widen-cli export:csv [TOKEN]
```

By default, this will export to a CSV named `export.csv`. To generate a CSV with a different file name, you can run the command below:

```shell
$ ./widen-cli export:csv [TOKEN] -f [my-file-name].csv
```
### View Asset:
```shell
$ ./widen-cli asset [uuid] [token]
```
