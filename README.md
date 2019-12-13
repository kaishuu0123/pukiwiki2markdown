# pukiwiki2markdown

## Demo

* https://pukiwiki2markdown.saino.me/

## Development

```shell
$ php composer.phar install
$ php composer.phar start
```

## API

```shell
curl -XPOST <pukiwiki2markdown URL>/api/v1/convert -H 'Content-Type: application/json' -d '{"body": "*Header1\n**Header2\n"}'
```
