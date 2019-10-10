# upload
Web system to accept uploads via temporary links

## Requirements
In order to function as intended, the following criteria need to be met:
  * A web server with PHP support
  * A directory outside the web root that the web server user has read and write access to
  * An authentication mechanism that populates the server variable REMOTE_USER
  * A mariaDB database (mySQL should also work but is untested)
  
## Setup
 1. Clone the repo into a web-accessible directory
 1. Copy ```config.php.example``` to ```config.php``` and update the relevant settings
 1. Configure the web server to protect the application root directory with some 
    authentication method
 1. Exclude the subdirectories ```link``` and ```images``` from authentication
 1. Load the database dump ```database.sql``` into the database specified in ```config.php```

## General usage
Each authenticated user will be able to create upload links and download any files uploaded 
to their created links. Once a link has been created, anyone with the link can upload a single 
file to the system, which will then be made available to the user who created the link.

There is no provision for making uploaded files available to other users within the system. 
The only user able to access an uploaded file is the one who created the link.

A link will accept exactly one upload. Once a file has been successfully saved by the system,
that link will no longer accept uploads.
