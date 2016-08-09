OpenProvider Bulk Toolkit
=========================

Toolkit containing functions that can be used to make changes to OpenProvider in bulk safely.

Features:

* Change DNS entries in bulk 

Example
-------

We add an `A` record to all domains that already have an `MX` record: 

```php
class AddARecordToOnceHavingMx implements DnsTransformer
{
    public function transform($domain, array $records)
    {
        $mxRecord = $this->getMx($records);
        if (!$mxRecord) {
            return $records;
        }
        
        $records[] = new DnsRecord('A', 'extra', '1.2.3.4', null, 3600);
        
        return $records;
    }
    
    protected function getMx(array $records)
    {
        /** @var DnsRecord $record */
        foreach ($records as $record) {
            if ($record->getType() === 'MX' && $record->getName() === '') {
                return $record;
            }
        }

        return null;
    }
}

$openProviderService = new OpenProviderService('user', 'pass');
$dnTransformService = new DnsTransformService($openProviderService);
$dnsTransformService->transform(new AddARecordToOnceHavingMx);
```

License
-------

Licended under the MIT License.
