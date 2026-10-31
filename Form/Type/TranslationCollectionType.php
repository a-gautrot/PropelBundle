<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Form\Type;

use Propel\Bundle\PropelBundle\Form\EventListener\TranslationCollectionFormListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * # TranslationCollectionType
 * form type for i18n-columns in propel
 *
 * @author Patrick Kaufmann
 * @api
 * @template TData
 * @extends AbstractType<TData>
 */
class TranslationCollectionType extends AbstractType
{
    /**
     * @param FormBuilderInterface<TData|null> $builder
     * @param array<array-key, mixed> $options
     * @return void
     * @throws MissingOptionsException
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!isset($options['entry_options']['data_class'])) {
            throw new MissingOptionsException('data_class must be set');
        }
        if (!isset($options['entry_options']['columns'])) {
            throw new MissingOptionsException('columns must be set');
        }

        $listener = new TranslationCollectionFormListener(
            $options['languages'],
            $options['entry_options']['data_class']
        );
        $builder->addEventSubscriber($listener);
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     * @throws AccessException
     */
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['languages']);

        $resolver->setDefaults([
            'entry_type' => TranslationType::class,
            'allow_add' => false,
            'allow_delete' => false,
            'entry_options' => [
                'data_class' => null,
                'columns' => null
            ]
        ]);
    }

    /**
     * @return string|null
     */
    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    /**
     * @return string
     */
    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'propel_translation_collection';
    }
}
