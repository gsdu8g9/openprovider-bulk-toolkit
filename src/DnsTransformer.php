<?php

namespace OpenProviderTools;

interface DnsTransformer
{
    /**
     * @param string $domain
     * @param DnsRecord[] $records
     * @return DnsRecord[]
     */
    public function transform($domain, array $records);
}
