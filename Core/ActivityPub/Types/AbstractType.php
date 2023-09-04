<?php
namespace Minds\Core\ActivityPub\Types;

use DateTime;
use Minds\Core\ActivityPub\Attributes\ExportProperty;
use Minds\Entities\ExportableInterface;
use Minds\Entities\User;
use ReflectionClass;
use Twilio\Rest\Preview\BulkExports\ExportInstance;

abstract class AbstractType implements ExportableInterface
{
    #[ExportProperty]
    protected string $type;

    protected array $contexts = [
        'https://www.w3.org/ns/activitystreams',
    ];

    public function getContextExport(): array
    {
        return [
            '@context' => count($this->contexts) === 1 ? $this->contexts[0] : $this->contexts
        ];
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function export(array $extras = []): array
    {
        $export = [ ];

        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute) {
                if ($attribute->getName() === ExportProperty::class && isset($this->{$property->getName()})) {

                    $value = $this->{$property->getName()};

                    if ($value instanceof ExportableInterface) {
                        $value = $value->export();
                    }

                    if (is_array($value)) {
                        foreach ($value as $k => $v) {
                            if ($v instanceof ExportableInterface) {
                                $value[$k] = $v->export();
                            }
                        }
                    }

                    if ($value instanceof DateTime) {
                        $value = $value->format('c');
                    }

                    $export[$property->getName()] = $value;
                }
            }
        }

        return $export;
    }

}
