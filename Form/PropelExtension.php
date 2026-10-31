<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Form;

use Override;
use Propel\Bundle\PropelBundle\Form\Type\ModelType;
use Propel\Bundle\PropelBundle\Form\Type\TranslationCollectionType;
use Propel\Bundle\PropelBundle\Form\Type\TranslationType;
use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony\Component\Form\ChoiceList\Factory\PropertyAccessDecorator;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * # PropelExtension
 * Represents the Propel form extension, which loads the Propel functionality.
 *
 * @author Joseph Rouff <rouffj@gmail.com>
 * @api
 */
class PropelExtension extends AbstractExtension
{

    /**
     * @var PropertyAccessorInterface
     */
    protected PropertyAccessorInterface $propertyAccessor;

    /**
     * @var ChoiceListFactoryInterface
     */
    protected ChoiceListFactoryInterface $choiceListFactory;

    /**
     * PropelExtension constructor.
     *
     * @param PropertyAccessorInterface|null $propertyAccessor
     * @param ChoiceListFactoryInterface|null $choiceListFactory
     */
    public function __construct(
        ?PropertyAccessorInterface  $propertyAccessor = null,
        ?ChoiceListFactoryInterface $choiceListFactory = null
    )
    {
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->choiceListFactory = $choiceListFactory ?: new PropertyAccessDecorator(
            new DefaultChoiceListFactory(),
            $this->propertyAccessor
        );
    }

    /**
     * @return FormTypeInterface<mixed>[]
     */
    #[Override]
    protected function loadTypes(): array
    {
        return [
            new ModelType($this->propertyAccessor, $this->choiceListFactory),
            new TranslationCollectionType(),
            new TranslationType()
        ];
    }

    /**
     * @return FormTypeGuesserInterface|null
     */
    #[Override]
    protected function loadTypeGuesser(): ?FormTypeGuesserInterface
    {
        return new TypeGuesser();
    }
}
