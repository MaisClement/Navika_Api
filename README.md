# Navika API

## Needed

## Installation

```
composer require
```
Rename `EXAMPLE.env` to `.env` and edit it to include your database informations

### Firebase
This project use Firebase to send notification to user
-> Config 

/config/key/firebase-adminsdk.json

/config/packages/config.yaml

## Initialization
Create the database and execute the migrations
```
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```


Run initilization command
```
php bin/console app:sncf:init
php bin/console app:town:init communes.geojson zip_code.json
```
You can get `communes.geojson` [here](https://github.com/gregoiredavid/france-geojson/blob/master/communes.geojson), and `zip_code.json` is a modified version of [Base officielle des codes postaux ](https://www.data.gouv.fr/fr/datasets/base-officielle-des-codes-postaux/) that you can get [here](https://github.com/MaisClement/Navika_Api/tree/master/data/zip_code.json)

## Command

### Data provider
```
  app:provider:add      Add a data provider (can be for GTFS or GTFS)
    usage   : app:provider:add <type> <ID> <Name> <Area> <OpenData Id> 
    example : app:provider:add "bikes" "VELIB" "Vélib‘" "Paris" "5a4e60f2b595080ee8014056" 
  
  app:provider:refresh  Refresh data for provider

  app:provider:clear    Delete data of the given provider
    usage   : app:provider:clear <ID>
    usage   : app:provider:clear --all
  
  app:provider:remove   Remove a data provider
    usage   : app:provider:remove <ID>
    usage   : app:provider:remove --all

  app:provider:reset    Reset status for all provider (in case of failed update)

  app:provider:list     List all provider
```

### Update Data
```
  app:gbfs:update       Update bikes system data

  app:gtfs:update       Update transit system data

  app:trafic:update     Update trafic data

  app:trafic:update:IDFM Update trafic data (IDFM SPECIFIC)

  app:timetables:update Update maps and timetable for line (IDFM SPECIFIC)

  app:maps:update       Update network maps (IDFM SPECIFIC)
```

### Others commands
```
  app:sncf:update       Update SNCF stops (not needed as it's automaticaly called in app:gtfs:update)
  
  app:gtfs:stoparea     Update StopArea from gtfs data

  app:gtfs:stoproute    Generate a stopRoute table (used for stops search)
```
