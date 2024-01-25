<?php

class PulsarProducerConfigurationMock
{
    public function setSchema(...$args)
    {
        return $this;
    }
}
class PulsarConsumerConfigurationMock
{
    public function setSchema(...$args)
    {
        return $this;
    }

    public function setConsumerType($type)
    {
        return $this;
    }
}
class PulsarSchemaTypeMock
{
    public const AVRO = 4;
    public const JSON = 2;
}
class PulsarClientMock
{
    public function init()
    {
    }

    public function createProducer()
    {
        return new PulsarProducerMock();
    }

    public function subscribe()
    {
        return new PulsarConsumerMock();
    }

    public function close(): void
    {
    }
}
class PulsarClientConfigurationMock
{
    public function setLogLevel(int $logLevel): self
    {
        return $this;
    }

    public function setUseTls(bool $useTl): self
    {
        return $this;
    }

    public function setTlsTrustCertsFilePath(string $path): self
    {
        return $this;
    }

    public function setTlsAllowInsecureConnection(bool $allow): self
    {
        return $this;
    }
}
class PulsarProducerMock
{
    public function send($payload, $options = []): int
    {
        return 1;
    }

    public function connect(): void
    {
    }

    public function close(): void
    {
    }
}
class PulsarConsumerMock
{
    public const ConsumerShared = 3;

    public function receive()
    {
        return new PulsarMessageMock();
    }

    public function acknowledge(PulsarMessageMock $messageMock): void
    {
    }

    public function negativeAcknowledge(PulsarMessageMock $messageMock): void
    {
    }

    public function connect(): void
    {
    }

    public function close(): void
    {
    }
}
class PulsarMessageBuilderMock
{
    public function setContent($content)
    {
        return $this;
    }

    public function build()
    {
        return new PulsarMessageMock();
    }

    public function setEventTimestamp(int $timestamp)
    {
        return $this;
    }
}
class PulsarMessageMock
{
    public function getDataAsString(): string
    {
        return '';
    }
}
class PulsarResultMock
{
    public const ResultOk = 1;
}

if (!class_exists('Pulsar')) {
    class_alias('PulsarProducerConfigurationMock', 'Pulsar\ProducerConfiguration');
    class_alias('PulsarConsumerConfigurationMock', 'Pulsar\ConsumerConfiguration');
    class_alias('PulsarSchemaTypeMock', 'Pulsar\SchemaType');
    class_alias('PulsarClientMock', 'Pulsar\Client');
    class_alias('PulsarClientConfigurationMock', 'Pulsar\ClientConfiguration');
    class_alias('PulsarProducerMock', 'Pulsar\Producer');
    class_alias('PulsarConsumerMock', 'Pulsar\Consumer');
    class_alias('PulsarMessageBuilderMock', 'Pulsar\MessageBuilder');
    class_alias('PulsarMessageMock', 'Pulsar\Message');
    class_alias('PulsarResultMock', 'Pulsar\Result');
}
