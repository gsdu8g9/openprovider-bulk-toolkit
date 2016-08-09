<?php

namespace OpenProviderTools;

class OpenProviderService
{
    /**
     * @var string
     */
    protected $openProviderUsername;

    /**
     * @var string
     */
    protected $openProviderPassword;

    /**
     * @param string $openProviderUsername
     * @param string $openProviderPassword
     */
    public function __construct($openProviderUsername, $openProviderPassword)
    {
        $this->openProviderUsername = $openProviderUsername;
        $this->openProviderPassword = $openProviderPassword;
    }

    /**
     * @param int $start
     * @param int $limit
     * @return string[]
     * @throws \Exception
     */
    public function getDomains($start, $limit)
    {
        $api = new \OP_API ('https://api.openprovider.eu');

        $request = new \OP_Request;
        $request
            ->setCommand('searchDomainRequest')
            ->setAuth(['username' => $this->openProviderUsername, 'password' => $this->openProviderPassword])
            ->setArgs([
                'offset' => $start,
                'limit' => $limit,
                'status' => 'ACT',
            ]);

        $reply = $api->setDebug(0)->process($request);
        if ($reply->getFaultCode() > 0) {
            throw new \Exception("Gatewway error. Code {$reply->getFaultCode()} with message: `{$reply->getFaultString()}`.");
        }

        $response = $reply->getValue();

        return array_map(function ($line) {
            return $line['domain']['name'] . '.' . $line['domain']['extension'];
        }, $response['results']);
    }

    /**
     * @param string $domain
     * @param bool $excludeImmutableTypes
     * @return DnsRecord[]
     */
    public function getDnsRecords($domain, $excludeImmutableTypes = true)
    {
        $api = new \OP_API ('https://api.openprovider.eu');

        $request = new \OP_Request;
        $request
            ->setCommand('retrieveZoneDnsRequest')
            ->setAuth(['username' => $this->openProviderUsername, 'password' => $this->openProviderPassword])
            ->setArgs([
                'name' => $domain,
                'withRecords' => 1
            ]);

        $reply = $api->setDebug(0)->process($request);
        if ($reply->getFaultCode() > 0) {
            throw new \Exception("Gatewway error. Code {$reply->getFaultCode()} with message: `{$reply->getFaultString()}`.");
        }

        $response = $reply->getValue();

        $records = array_map(
            [$this, 'parseRecord'],
            $response['records'],
            array_fill(0, count($response['records']), $domain)
        );

        if ($excludeImmutableTypes) {
            $records = array_filter($records, function (DnsRecord $record) {
                return !in_array($record->getType(), $this->getImmutableRecords());
            });
        }

        return $records;
    }

    /**
     * @param array $record
     * @param string $domain
     * @return DnsRecord
     */
    protected function parseRecord(array $record, $domain)
    {
        $name = $record['name'];

        if ($this->stringEndsWith($name, ".{$domain}")) {
            $name = substr($name, 0, -strlen($domain) - 1);
        }
        if ($this->stringEndsWith($name, $domain)) {
            $name = substr($name, 0, -strlen($domain));
        }

        return new DnsRecord(
            $record['type'],
            $name,
            $record['value'],
            isset($record['prio']) ? $record['prio'] : null,
            (int)$record['ttl']
        );
    }

    /**
     * @param string $haystack
     * @param string $postfix
     * @return bool
     */
    protected function stringEndsWith($haystack, $postfix)
    {
        $postfixOccurence = strpos($haystack, $postfix);
        if ($postfixOccurence === false) {
            return false;
        }

        return $postfixOccurence + strlen($postfix) === strlen($haystack);
    }

    /**
     * @param string $domain
     * @param DnsRecord[] $records
     */
    public function writeDnsRecords($domain, array $records)
    {
        $immutableRecords = array_filter($records, function (DnsRecord $record) {
            return in_array($record->getType(), $this->getImmutableRecords());
        });

        if (count($immutableRecords) > 0) {
            throw new \InvalidArgumentException("Cannot use this method to write NS or SOA records. API does not allow it.");
        }

        $api = new \OP_API ('https://api.openprovider.eu');

        $request = new \OP_Request;
        $request
            ->setCommand('modifyZoneDnsRequest')
            ->setAuth(['username' => $this->openProviderUsername, 'password' => $this->openProviderPassword])
            ->setArgs([
                'domain' => $this->getDomainConfig($domain),
                'type' => 'master',
                'records' => array_map([$this, 'recordToConfig'], $records)
            ]);

        $reply = $api->setDebug(0)->process($request);
        if ($reply->getFaultCode() > 0) {
            throw new \Exception("Gatewway error. Code {$reply->getFaultCode()} with message: `{$reply->getFaultString()}`.");
        }
    }

    /**
     * @param string $domain
     * @return string[]
     */
    protected function getDomainConfig($domain)
    {
        $result = preg_match('/^([^.]+)\.(.+)$/', $domain, $matches);
        if ($result !== 1) {
            throw new \InvalidArgumentException("Could not handle domain `{$domain}`.");
        }

        return [
            'name' => $matches[1],
            'extension' => $matches[2]
        ];
    }

    /**
     * @param DnsRecord $record
     * @return array
     */
    protected function recordToConfig(DnsRecord $record)
    {
        $response = [
            'type' => $record->getType(),
            'name' => $record->getName(),
            'value' => $record->getValue(),
            'ttl' => $record->getTtl(),
        ];

        if ($record->hasPriority()) {
            $response['prio'] = $record->getPriority();
        }

        return $response;
    }

    /**
     * @return string[]
     */
    protected function getImmutableRecords()
    {
        return ['NS', 'SOA'];
    }
}
