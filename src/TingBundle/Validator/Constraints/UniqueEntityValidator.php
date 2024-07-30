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

use CCMBenchmark\Ting\Repository\RepositoryFactory;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

if (Kernel::VERSION_ID >= 60000) {
    /**
     * UniqueEntityValidator
     */
    class UniqueEntityValidator extends ConstraintValidator
    {
        /**
         * @var RepositoryFactory
         */
        private $repositoryFactory;

        /**
         * UniqueEntityValidator constructor.
         *
         * @param RepositoryFactory $repositoryFactory
         */
        public function __construct(RepositoryFactory $repositoryFactory)
        {
            $this->repositoryFactory = $repositoryFactory;
        }

        /**
         * @param mixed      $value
         * @param Constraint $constraint
         *
         * @throws UnexpectedTypeException
         */
        public function validate(mixed $value, Constraint $constraint)
        {
            if (!$constraint instanceof UniqueEntity) {
                throw new UnexpectedTypeException($constraint, UniqueEntity::class);
            }

            $repository = $this->repositoryFactory->get($constraint->repository);

            $metadata = $repository->getMetadata();

            $criteria = [];
            $fields = (array)$constraint->fields;

            foreach ($fields as $field) {
                $getter = $metadata->getGetter($field);
                $criteria[$field] = $value->$getter();
            }

            $myEntity = $repository->getOneBy($criteria);

            if ($myEntity !== null) {
                $validationFailed = true;
                $identityFields = (array)$constraint->identityFields;
                if ($identityFields !== []) {
                    $validationFailed = false;
                    foreach ($identityFields as $identityField) {
                        $getter = $metadata->getGetter($identityField);
                        if ($value->$getter() !== $myEntity->$getter()) {
                            $validationFailed = true;
                            break;
                        }
                    }
                }
                if ($validationFailed === true) {
                    $this->context->buildViolation($constraint->message)
                                  ->setParameter('{{ data }}', implode(', ', $criteria))
                                  ->addViolation();
                }
            }
        }
    }
} else {
    /**
     * UniqueEntityValidator
     */
    class UniqueEntityValidator extends ConstraintValidator
    {
        /**
         * @var RepositoryFactory
         */
        private $repositoryFactory;

        /**
         * UniqueEntityValidator constructor.
         *
         * @param RepositoryFactory $repositoryFactory
         */
        public function __construct(RepositoryFactory $repositoryFactory)
        {
            $this->repositoryFactory = $repositoryFactory;
        }

        /**
         * @param mixed      $value
         * @param Constraint $constraint
         *
         * @throws UnexpectedTypeException
         */
        public function validate($value, Constraint $constraint)
        {
            if (!$constraint instanceof UniqueEntity) {
                throw new UnexpectedTypeException($constraint, UniqueEntity::class);
            }

            $repository = $this->repositoryFactory->get($constraint->repository);

            $metadata = $repository->getMetadata();

            $criteria = [];
            $fields = (array)$constraint->fields;

            foreach ($fields as $field) {
                $getter = $metadata->getGetter($field);
                $criteria[$field] = $value->$getter();
            }

            $myEntity = $repository->getOneBy($criteria);

            if ($myEntity !== null) {
                $validationFailed = true;
                $identityFields = (array)$constraint->identityFields;
                if ($identityFields !== []) {
                    $validationFailed = false;
                    foreach ($identityFields as $identityField) {
                        $getter = $metadata->getGetter($identityField);
                        if ($value->$getter() !== $myEntity->$getter()) {
                            $validationFailed = true;
                            break;
                        }
                    }
                }
                if ($validationFailed === true) {
                    $this->context->buildViolation($constraint->message)
                                  ->setParameter('{{ data }}', implode(', ', $criteria))
                                  ->addViolation();
                }
            }
        }
    }
}
