<?php

declare(strict_types=1);

namespace App\General\Application\DTO;

use App\General\Application\DTO\Interfaces\RestDtoInterface;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use BadMethodCallException;
use LogicException;
use Override;

use function array_filter;
use function array_key_exists;
use function count;
use function current;
use function method_exists;
use function sprintf;
use function ucfirst;

/**
 * @package App\General
 */
abstract class RestDto implements RestDtoInterface
{
    /**
     * DTO property mappings to setter method.
     *
     * Example:
     *  static protected $mappings = [
     *      'someProperty' => 'methodInYourDtoClass',
     *  ]
     *
     * This will call below method in your DTO class:
     *  protected function methodInYourDtoClass($entity, $value)
     *
     * And in that method make all necessary that you need to set that specified value.
     *
     * @var array<string, string>
     */
    protected static array $mappings = [];

    protected ?string $id = null;

    /**
     * An array of 'visited' setter properties of current dto.
     *
     * @var array<int, string>
     */
    private array $visited = [];

    #[Override]
    public function setId(string $id): self
    {
        $this->setVisited('id');
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    #[Override]
    public function getVisited(): array
    {
        return array_filter($this->visited, static fn (string $property): bool => $property !== 'id');
    }

    #[Override]
    public function setVisited(string $property): self
    {
        $this->visited[] = $property;

        return $this;
    }

    /**
     * Method to update specified entity with DTO data.
     */
    #[Override]
    public function update(EntityInterface $entity): EntityInterface
    {
        foreach ($this->getVisited() as $property) {
            if (array_key_exists($property, static::$mappings)) {
                $this->{static::$mappings[$property]}($entity, $this->{$property});

                continue;
            }

            // Determine setter method
            $setter = 'set' . ucfirst($property);
            // Update current dto property value
            $entity->{$setter}($this->{$property});
        }

        return $entity;
    }

    /**
     * Method to patch current dto with another one.
     *
     * @throws LogicException|BadMethodCallException
     */
    #[Override]
    public function patch(RestDtoInterface $dto): self
    {
        foreach ($dto->getVisited() as $property) {
            // Determine getter method
            $getter = $this->determineGetterMethod($dto, $property);
            // Determine setter method
            $setter = 'set' . ucfirst($property);
            // Update current dto property value
            $this->{$setter}($dto->{$getter}());
        }

        return $this;
    }

    /**
     * Method to determine used getter method for DTO property.
     *
     * @throws LogicException
     */
    private function determineGetterMethod(RestDtoInterface $dto, string $property): string
    {
        $getter = $this->getGetterMethod($dto, $property);

        // Oh noes - required getter method does not exist
        if ($getter === null) {
            $message = sprintf(
                'DTO class \'%s\' does not have getter method property \'%s\' - cannot patch dto',
                $dto::class,
                $property
            );

            throw new BadMethodCallException($message);
        }

        return $getter;
    }

    /**
     * @throws LogicException
     */
    private function getGetterMethod(RestDtoInterface $dto, string $property): ?string
    {
        $getters = [
            'get' . ucfirst($property),
            'is' . ucfirst($property),
            'has' . ucfirst($property),
        ];
        $getterMethods = array_filter($getters, static fn (string $method): bool => method_exists($dto, $method));

        return $this->validateGetterMethod($property, $getterMethods);
    }

    /**
     * @param array<int, string> $getterMethods
     *
     * @throws LogicException
     */
    private function validateGetterMethod(string $property, array $getterMethods): ?string
    {
        if (count($getterMethods) > 1) {
            $message = sprintf(
                'Property \'%s\' has multiple getter methods - this is insane!',
                $property
            );

            throw new LogicException($message);
        }

        $method = current($getterMethods);

        return $method === false ? null : $method;
    }
}
