### v1.0

create your Controller and with a construct method inject our service as below;

```php
class TryOtoController
{
    public $service;

    public function __construct()
    {
        $this->service = new TryotoService;
    }
    
    /// your code below

```

the constructed service will automatically generate for you the token instance and cache id for 58 minutes by default.
the token is valid for only 1 hour in official documents, so I hardcoded that value for some certain reasons to 58 minutes fixed. you can change it from config file for your needs
