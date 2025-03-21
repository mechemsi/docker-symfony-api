<?php

declare(strict_types=1);

namespace App\User\Transport\Form\DataTransformer;

use App\User\Application\Resource\UserGroupResource;
use App\User\Domain\Entity\UserGroup;
use Override;
use Stringable;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;

use function array_map;
use function is_array;
use function sprintf;

/**
 * @package App\User
 */
class UserGroupTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly UserGroupResource $resource,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * Transforms an array of objects (UserGroup) to an array of strings (UserGroup id).
     *
     * @psalm-param array<int, string|UserGroup>|mixed $value
     *
     * @psalm-return array<array-key, string>
     */
    #[Override]
    public function transform(mixed $value): array
    {
        $callback = static fn (UserGroup | Stringable $userGroup): string =>
            $userGroup instanceof UserGroup ? $userGroup->getId() : (string)$userGroup;

        return is_array($value) ? array_map($callback, $value) : [];
    }

    /**
     * {@inheritdoc}
     *
     * Transforms an array of strings (UserGroup id) to an array of objects (UserGroup).
     *
     * @psalm-param array<int, string>|mixed $value
     *
     * @throws Throwable
     *
     * @psalm-return array<array-key, UserGroup>|null
     */
    #[Override]
    public function reverseTransform(mixed $value): ?array
    {
        return is_array($value)
            ? array_map(
                fn (string $groupId): UserGroup => $this->resource->findOne($groupId, false) ??
                    throw new TransformationFailedException(
                        sprintf('User group with id "%s" does not exist!', $groupId),
                    ),
                $value,
            )
            : null;
    }
}
