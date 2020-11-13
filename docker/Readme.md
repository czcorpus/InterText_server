# InterText server docker setup

This folder contains a self-maintained Docker setup for the InterText server. To use the setup you need to first build the *intertext* image using the Dockerfile below and then you can start the service using the provided docker-compose configuration. Of course, this all assumes you have the official docker and docker-compose commands installed on your system.

## Dockerfile

As there is no image at any public repository at the moment, you need to run this script to build the image locally. The script clones the official Github repository and creates an image using the latest sources. You need only the files from this folder to build the image.

If you want *hunalign* functionality, you need to acquire the binary (eg. from the GUI version of the program), copy it to this folder (same as the Dockerfile) and uncomment the last line of the Dockerfile.

Once you are ready, simply run:

```
docker build -t intertext .
```

This will take a little while. If everything is succesful, you should see your *intertext* image on the list by running `docker images`.

## docker-compose

This script will run the service and the database instance required for it to function. 

The configuration mounts a couple of scripts to initialize the database the first time the image is created. These will create the default users and tables needed for the page to load for the first time.

We also mount the import folder to `/import` if you want to use the CLI tools to import many files.

To run the service, simply run the folowing command in this folder:

```
docker-compose up -d
```

Then you can observe the running processes:

```
docker-compose ps
```

And the logs:

```
docker-compose logs
```

To enter the terminal to use the CLI tools use the following command:

```
docker exec -it intertext_www_1 bash
```

Then simply enter the `cli` dir and try out all the commands there. Remeber, you can share the files between the docker containter and the host using the `./import` dir (mounted as `/import` inside the container).
