<?php
/***********************************************************************
 *
 * Ting Bundle - Symfony Bundle for Ting
 * ==========================================
 *
 * Copyright (C) 2014 CCM Benchmark Group. (http://www.ccmbenchmark.com)
 *
 ***********************************************************************
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you
 * may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 **********************************************************************/

namespace CCMBenchmark\TingBundle\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Class UniqueEntity
 *
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class UniqueEntity extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Another entity exists for this data: {{ data }}';

    /**
     * @var string
     */
    public $repository;

    /**
     * @var array
     */
    public $fields = array();

    /**
     * @var array
     */
    public $identityFields = array();

    /**
     * Accepts named arguments, or the legacy options array: #[UniqueEntity(fields: ['email'], repository: UserRepository::class)]
     *
     * @param array|null $options legacy form: ['fields' => [...], 'repository' => ..., 'identityFields' => [...]]
     */
    #[HasNamedArguments]
    public function __construct(
        mixed $options = null,
        ?array $fields = null,
        ?string $repository = null,
        ?array $identityFields = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        $options = \is_array($options) ? $options : [];
        $values = array_filter([
            'fields' => $fields ?? $options['fields'] ?? null,
            'repository' => $repository ?? $options['repository'] ?? null,
            'identityFields' => $identityFields ?? $options['identityFields'] ?? null,
            'message' => $message ?? $options['message'] ?? null,
        ], static fn (mixed $value): bool => $value !== null);
        $groups ??= $options['groups'] ?? null;
        $payload ??= $options['payload'] ?? null;

        // Before Symfony 7.4 the parent validates and assigns the options itself.
        if (method_exists(Constraint::class, 'normalizeOptions') && !method_exists(Constraint::class, 'areRequiredOptionsHandledByChildConstructor')) {
            parent::__construct($values, $groups, $payload);

            return;
        }

        parent::__construct(null, $groups, $payload);

        $missing = array_diff(['fields', 'repository'], array_keys($values));
        if ($missing !== []) {
            throw new MissingOptionsException(sprintf('The options "%s" must be set for constraint "%s".', implode('", "', $missing), static::class), $missing);
        }
        foreach ($values as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * @return string
     */
    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * @return array
     */
    public function getRequiredOptions(): array
    {
        return ['fields', 'repository'];
    }
}
