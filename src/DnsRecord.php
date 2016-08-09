<?php

namespace OpenProviderTools;

class DnsRecord
{
    const NAME_WILDCARD = '*';

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var int|null
     */
    protected $priority;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * @param string $type
     * @param string $name
     * @param string $value
     * @param int|null $priority
     * @param int $ttl
     */
    public function __construct($type, $name, $value, $priority, $ttl)
    {
        $this->type = $type;
        $this->name = $name;
        $this->value = $value;
        $this->priority = $priority;
        $this->ttl = $ttl;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function hasPriority()
    {
        return $this->priority !== null;
    }

    /**
     * @return int|null
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }
}
