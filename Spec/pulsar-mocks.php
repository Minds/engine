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
    const AVRO = 4;
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
    public function send($message): int
    {
        return 1;
    }
}
class PulsarConsumerMock
{
    const ConsumerShared = 3;

    public function receive()
    {
        return new PulsarMessageMock();
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
    public function getDataAsString()
    {
    }
}
class PulsarResultMock
{
    const ResultOk = 1;
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
