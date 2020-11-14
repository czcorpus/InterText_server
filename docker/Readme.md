# InterText server docker setup

This folder contains a self-maintained Docker setup for the InterText server. To use the setup you can start the service using the provided docker-compose configuration. Of course, this all assumes you have the official docker and docker-compose commands installed on your system.

## docker-compose

This script will run the service (based on PHP and Apache) and the necesary database instance (mysql).

The configuration mounts a couple of scripts to initialize the database the first time the image is created. These will create the default users and tables needed for the page to load for the first time. To login simply use "admin" with the password "test". You can later change these accounts and their passwords from the web interface.

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

Then simply enter the `cli` directory and try out the commands there. Remeber, you can share the files between the docker containter and the host using the `./import` directory (mounted as `/import` inside the container).

## Dockerfile

The image is hosted on a public dockerhub repository under the name *speechclarinpl/intertext*, but if you want you can build the image locally. This may be useful if the public image isn't up-to-date or if you simply want to modify something. The Dockerfile clones the official Github repository and creates an image using the latest sources. You need only the files from this directory to build the image (the sources are always pulled from Github).

If you want *hunalign* functionality, you need to acquire the binary (eg. from the GUI version of the program), copy it to this folder (same as the Dockerfile) and uncomment the last line of the Dockerfile.

Once you are ready, simply run:

```
docker build -t intertext .
```

This will take a little while. If everything is succesful, you should see your *intertext* image on the list by running `docker images`.
