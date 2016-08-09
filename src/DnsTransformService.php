<?php

namespace OpenProviderTools;

class DnsTransformService
{
    /**
     * @var int
     */
    protected static $perPage = 100;

    /**
     * @var OpenProviderService
     */
    protected $openProviderService;

    /**
     * @param OpenProviderService $openProviderService
     */
    public function __construct(OpenProviderService $openProviderService)
    {
        $this->openProviderService = $openProviderService;
    }

    /**
     * @param DnsTransformer $transformer
     */
    public function transform(DnsTransformer $transformer)
    {
        $page = 0;
        do {
            $domains = $this->openProviderService->getDomains(self::$perPage * $page, self::$perPage);
            foreach ($domains as $domain) {
                $this->transformDomain($domain, $transformer);
            }
            $page++;
        } while (count($domains) > 0);
    }

    /**
     * @param string $domain
     * @param DnsTransformer $transformer
     */
    public function transformDomain($domain, DnsTransformer $transformer)
    {
        $currentRecords = $this->openProviderService->getDnsRecords($domain);

        $newRecords = $transformer->transform($domain, $currentRecords);

        if ($newRecords === $currentRecords) {
            return;
        }

        $this->openProviderService->writeDnsRecords($domain, $newRecords);
    }
}
