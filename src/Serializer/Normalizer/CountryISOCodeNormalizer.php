<?php

namespace App\Serializer\Normalizer;

use App\Entity\Country;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\{
    AbstractObjectNormalizer,
    ContextAwareNormalizerInterface,
    DenormalizerInterface,
    NormalizerAwareInterface,
    NormalizerAwareTrait
};

class CountryISOCodeNormalizer implements ContextAwareNormalizerInterface, DenormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'COUNTRY_ISO_CODE_NORMALIZER_ALREADY_CALLED';

    private const TRAIT = 'App\Entity\Traits\CountryTrait';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * CountryISOCodeNormalizer constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @inheritDoc
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        $context[AbstractObjectNormalizer::CIRCULAR_REFERENCE_HANDLER] = function ($object, $format, $context) {
            return [$object->getId()];
        };
        $data = $this->normalizer->normalize($object, $format, $context);

        if (isset($data['countryId'])) {
            $country = $this->em->getRepository(Country::class)
                ->findOneBy(['id' => $data['countryId']]);
            $data['countryISOCode'] = $country->getCountry();
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        // avoid recursion: only call once per object
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return 'object' === gettype($data) && in_array(self::TRAIT, class_uses($data));
    }

    /**
     * @inheritDoc
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (isset($data['countryISOCode'])) {
            $country = $this->em->getRepository(Country::class)
                ->findOneBy(['country' => $data['countryISOCode']]);
            $data['countryId'] = $country->getId();
        }

        return $this->normalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return 'object' === gettype($data) && in_array(self::TRAIT, class_uses($data));
    }
}
