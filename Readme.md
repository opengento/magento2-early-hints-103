# Early Hints by Opengento

POC for adding preloading content headers / Early hints approach

## Setup / prerequirement

Magento 2 Open Source or Commerce edition is required.
Nginx / Varnish (apache not tested yet)

### Composer installation

Run the following composer command:

```
composer require opengento/module-early-hints-103
```

### Setup the module

Run the following magento command:

```
bin/magento setup:upgrade
``` 

### Varnish VCL file

```
sub vcl_backend_response {
  if (beresp.http.Surrogate-Control ~ "OpengentoBroadcast/1.0") {
      set beresp.do_stream = true;
      set beresp.ttl = 0s;
  }
```

## Support

Raise a new [request](https://github.com/opengento/module-early-hints-103/issues) to the issue tracker.

## Authors

- **Opengento Community** - *Lead*
    - [![Twitter Follow](https://img.shields.io/twitter/follow/opengento.svg?style=social)](https://twitter.com/opengento)

## License

This project is licensed under the MIT License - see the [LICENSE](./LICENSE) details.


