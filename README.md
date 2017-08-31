# Geo-Timezone PHP Library
Based on the [node-geo-tz package](https://github.com/evansiroky/node-geo-tz), this PHP library calculates the timezone associated to a particular location based on coordinates (latitude, longitude) and timestamp
reference. Therefore, this library provides the local date of a particular location in any moment too.
In addition, it is based on the timezones boundaries extracted by [timezone-boundary-builder](https://github.com/evansiroky/timezone-boundary-builder) tool, so it is necessary to always use the latest version of this package.
In order to improve the timezone search through the boundaries, node-geo-tz proposes to create a tree of directories based on dividing the Earth in quadrants and sub-quadrants iteratively. This tree is called "data.zip" in the reference library.


## Requirements
GEOS PHP extension is needed to run library. So, you should download and compile it running the script bin/compile-geos
.sh; then, the library called "geos.so" will be added to /usr/lib/php.
As you can see, this script contains the installation of some php extensions that will be necessary in the next
step of the installation process.

Once you have compiled the GEOS PHP extension, you should run the composer file, so the rest of necessary libraries
will be installed.


## Usage
There are two main classes:
* Calculator: provides the timezone name or the local date associated to a particular latitude, longitude and timestamp.
* UpdaterData: script that downloads the last version of the timezone boundaries data and creates the tree of directories (data.zip).