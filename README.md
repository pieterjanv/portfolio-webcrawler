# price_crawler

Verzameling modules voor het definiëren van een web crawler en een toepassing tot crawler van bierprijzen.

Middels op te geven callbacks kan gespecifieerd worden:

- welke urls toe te voegen aan te bezoeken urls
- welke overige informatie in de bezochte pagina's op te slaan
- gegeven een url, te besluiten of deze url bezocht moet worden
- gegeven een url, te besluiten of deze toegevoegd moet worden aan het aantal bezochte urls

Door het implementeren van de CrawlerDbInterface is het mogelijk het opslagmedium vrij te kiezen.
