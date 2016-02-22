# Portphotio

## A lightweight library for handling small photo collections with JSON
No need for a database when you're only going to handle a few dozen images. Not having a database makes installation and backup a lot simpler. Portphotio simply names images by hashing their contents, stores them in the directory it's initializeed with, and writes them to a manifest.json file along with the name they were uploaded with. You can access entries and change their properties, such as name or order, or you can give them any arbitrary attributes you want, such as photographer, client, location, etc.
