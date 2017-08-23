# Geo-Timezone Library
Calculate timezone associated to a particular location based on coordinates (latitude, longitude) and timestamp
reference.
Therefore, this library provides the local date of a particular location in any moment too.


## Requirements
GEOS PHP extension is needed to run library. So, you should download and compile it running the script bin/compile-geos
.sh; then, the library called "geos.so" will be added to /usr/lib/php.
As you can see, this script contains the installation of some php extensions that will be necessary in the next
step of the installation process.

Once you have compiled the GEOS PHP extension, you should run the composer file, so the rest of necessary libraries
will be installed.