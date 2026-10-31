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

use Override;
use Propel\Bundle\PropelBundle\Form\EventListener\TranslationFormListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * # TranslationType
 * Translation type class
 *
 * @author Patrick Kaufmann
 * @api
 * @template TData
 * @extends AbstractType<TData>
 */
class TranslationType extends AbstractType
{
    /**
     * {@inheritdoc}
     * @param FormBuilderInterface<TData|null> $builder
     * @param array<array-key, mixed> $options
     * @return void
     */
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(
            new TranslationFormListener($options['columns'], $options['data_class'])
        );
    }

    /**
     * {@inheritdoc}
     * @param OptionsResolver $resolver
     * @return void
     * @throws AccessException
     */
    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'data_class',
            'columns'
        ]);
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
    #[Override]
    public function getBlockPrefix(): string
    {
        return 'propel_translation';
    }
}
