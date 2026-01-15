<?php

namespace App\Entity;

use App\Entity\Traits\BlameableEntity;
use App\Repository\MedicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\TimestampableTrait;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MedicationRepository::class)]
#[ORM\Table(name: "medication")]
#[ORM\HasLifecycleCallbacks]
class Medication
{
    use TimestampableTrait;
    use BlameableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $name_pl = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $name_de = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $name_en = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $find = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $ingredients = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $allergens = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private ?bool $active = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $barcode = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $dosage_strength_pl = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $dosage_strength_de = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $dosage_strength_en = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $form_pl = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $form_de = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $form_en = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $pack_size_pl = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $pack_size_de = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $pack_size_en = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $atc_code = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $usage_category_pl = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $usage_category_de = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $usage_category_en = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $side_effects_pl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $side_effects_de = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $side_effects_en = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contraindications_pl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contraindications_de = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contraindications_en = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $brand_country = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $manufacturing_country = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private ?bool $categorized = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private ?bool $tagged = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $author_link = null;

    #[Assert\Length(max: 255, maxMessage: 'entity.slug.length.max')]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, unique: true)]
    private ?string $slug_en = null;

    #[Assert\Length(max: 255, maxMessage: 'entity.slug.length.max')]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, unique: true)]
    private ?string $slug_de = null;

    #[Assert\Length(max: 255, maxMessage: 'entity.slug.length.max')]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, unique: true)]
    private ?string $slug_pl = null;

    #[Assert\Length(max: 255, maxMessage: 'entity.slug_nr.length.max')]
    #[ORM\Column(type: Types::BIGINT, length: 255, nullable: true)]
    private ?int $slug_en_nr = null;

    #[Assert\Length(max: 255, maxMessage: 'entity.slug_nr.length.max')]
    #[ORM\Column(type: Types::BIGINT, length: 255, nullable: true)]
    private ?int $slug_de_nr = null;

    #[Assert\Length(max: 255, maxMessage: 'entity.slug_nr.length.max')]
    #[ORM\Column(type: Types::BIGINT, length: 255, nullable: true)]
    private ?int $slug_pl_nr = null;

    #[ORM\OneToMany(
        mappedBy: 'medication',
        targetEntity: MedicationCategory::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $medicationCategories;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
        $this->medicationCategories = new ArrayCollection();
        $this->active = true;
        $this->categorized = false;
        $this->tagged = false;
    }

    public function getMedicationCategories(): Collection
    {
        return $this->medicationCategories;
    }

    public function addMedicationCategory(MedicationCategory $medicationCategory): self
    {
        if (!$this->medicationCategories->contains($medicationCategory)) {
            $this->medicationCategories->add($medicationCategory);
            $medicationCategory->setMedication($this);
        }

        return $this;
    }

    public function removeMedicationCategory(MedicationCategory $medicationCategory): self
    {
        if ($this->medicationCategories->removeElement($medicationCategory)) {
            if ($medicationCategory->getMedication() === $this) {
                $medicationCategory->setMedication(null);
            }
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNamePl(): ?string
    {
        return $this->name_pl;
    }

    public function setNamePl(?string $name_pl): self
    {
        $this->name_pl = $name_pl;
        return $this;
    }

    public function getNameDe(): ?string
    {
        return $this->name_de;
    }

    public function setNameDe(?string $name_de): self
    {
        $this->name_de = $name_de;
        return $this;
    }

    public function getNameEn(): ?string
    {
        return $this->name_en;
    }

    public function setNameEn(?string $name_en): self
    {
        $this->name_en = $name_en;
        return $this;
    }

    public function getFind(): ?string
    {
        return $this->find;
    }

    public function setFind(?string $find): self
    {
        $this->find = $find;
        return $this;
    }

    public function __toString(): string
    {
        return (string) ($this->name_en ?? $this->find ?? $this->id);
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    public function getBrand()
    {
        return $this->brand;
    }

    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }

    public function getIngredients()
    {
        return $this->ingredients;
    }

    public function setIngredients($ingredients)
    {
        $this->ingredients = $ingredients;

        return $this;
    }

    public function getAllergens()
    {
        return $this->allergens;
    }

    public function setAllergens($allergens)
    {
        $this->allergens = $allergens;

        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }
    public function setBarcode(?string $barcode): self
    {
        $this->barcode = $barcode;
        return $this;
    }

    public function getDosageStrengthPl(): ?string
    {
        return $this->dosage_strength_pl;
    }
    public function setDosageStrengthPl(?string $dosage): self
    {
        $this->dosage_strength_pl = $dosage;
        return $this;
    }

    public function getDosageStrengthDe(): ?string
    {
        return $this->dosage_strength_de;
    }
    public function setDosageStrengthDe(?string $dosage): self
    {
        $this->dosage_strength_de = $dosage;
        return $this;
    }

    public function getDosageStrengthEn(): ?string
    {
        return $this->dosage_strength_en;
    }
    public function setDosageStrengthEn(?string $dosage): self
    {
        $this->dosage_strength_en = $dosage;
        return $this;
    }

    public function getFormPl(): ?string
    {
        return $this->form_pl;
    }
    public function setFormPl(?string $form): self
    {
        $this->form_pl = $form;
        return $this;
    }

    public function getFormDe(): ?string
    {
        return $this->form_de;
    }
    public function setFormDe(?string $form): self
    {
        $this->form_de = $form;
        return $this;
    }

    public function getFormEn(): ?string
    {
        return $this->form_en;
    }
    public function setFormEn(?string $form): self
    {
        $this->form_en = $form;
        return $this;
    }

    public function getPackSizePl(): ?string
    {
        return $this->pack_size_pl;
    }
    public function setPackSizePl(?string $pack_size): self
    {
        $this->pack_size_pl = $pack_size;
        return $this;
    }

    public function getPackSizeDe(): ?string
    {
        return $this->pack_size_de;
    }
    public function setPackSizeDe(?string $pack_size): self
    {
        $this->pack_size_de = $pack_size;
        return $this;
    }

    public function getPackSizeEn(): ?string
    {
        return $this->pack_size_en;
    }
    public function setPackSizeEn(?string $pack_size): self
    {
        $this->pack_size_en = $pack_size;
        return $this;
    }

    public function getAtcCode(): ?string
    {
        return $this->atc_code;
    }
    public function setAtcCode(?string $atc_code): self
    {
        $this->atc_code = $atc_code;
        return $this;
    }

    public function getUsageCategoryPl(): ?string
    {
        return $this->usage_category_pl;
    }
    public function setUsageCategoryPl(?string $cat): self
    {
        $this->usage_category_pl = $cat;
        return $this;
    }

    public function getUsageCategoryDe(): ?string
    {
        return $this->usage_category_de;
    }
    public function setUsageCategoryDe(?string $cat): self
    {
        $this->usage_category_de = $cat;
        return $this;
    }

    public function getUsageCategoryEn(): ?string
    {
        return $this->usage_category_en;
    }
    public function setUsageCategoryEn(?string $cat): self
    {
        $this->usage_category_en = $cat;
        return $this;
    }

    public function getContraindicationsPl(): ?string
    {
        return $this->contraindications_pl;
    }
    public function setContraindicationsPl(?string $contra): self
    {
        $this->contraindications_pl = $contra;
        return $this;
    }

    public function getContraindicationsDe(): ?string
    {
        return $this->contraindications_de;
    }
    public function setContraindicationsDe(?string $contra): self
    {
        $this->contraindications_de = $contra;
        return $this;
    }

    public function getContraindicationsEn(): ?string
    {
        return $this->contraindications_en;
    }
    public function setContraindicationsEn(?string $contra): self
    {
        $this->contraindications_en = $contra;
        return $this;
    }

    public function getSideEffectsPl(): ?string
    {
        return $this->side_effects_pl;
    }
    public function setSideEffectsPl(?string $side_effects): self
    {
        $this->side_effects_pl = $side_effects;
        return $this;
    }

    public function getSideEffectsDe(): ?string
    {
        return $this->side_effects_de;
    }
    public function setSideEffectsDe(?string $side_effects): self
    {
        $this->side_effects_de = $side_effects;
        return $this;
    }

    public function getSideEffectsEn(): ?string
    {
        return $this->side_effects_en;
    }
    public function setSideEffectsEn(?string $side_effects): self
    {
        $this->side_effects_en = $side_effects;
        return $this;
    }

    public function getBrandCountry(): ?string
    {
        return $this->brand_country;
    }

    public function setBrandCountry(?string $brand_country): self
    {
        $this->brand_country = $brand_country;
        return $this;
    }

    public function getManufacturingCountry(): ?string
    {
        return $this->manufacturing_country;
    }

    public function setManufacturingCountry(?string $manufacturing_country): self
    {
        $this->manufacturing_country = $manufacturing_country;
        return $this;
    }

    public function getCategorized()
    {
        return $this->categorized;
    }

    public function setCategorized($categorized)
    {
        $this->categorized = $categorized;

        return $this;
    }

    public function getTagged()
    {
        return $this->tagged;
    }

    public function setTagged($tagged)
    {
        $this->tagged = $tagged;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getAuthorLink(): ?string
    {
        return $this->author_link;
    }

    public function setAuthorLink(?string $author_link): self
    {
        $this->author_link = $author_link;
        return $this;
    }

    public function isIndexable(): ?bool
    {
        return $this->active;
    }

    public function getSlugEn(): ?string
    {
        return $this->slug_en;
    }

    public function setSlugEn(?string $slug_en): self
    {
        $this->slug_en = $slug_en;

        return $this;
    }

    public function getSlugDe(): ?string
    {
        return $this->slug_de;
    }

    public function setSlugDe(?string $slug_de): self
    {
        $this->slug_de = $slug_de;

        return $this;
    }

    public function getSlugPl(): ?string
    {
        return $this->slug_pl;
    }

    public function setSlugPl(?string $slug_pl): self
    {
        $this->slug_pl = $slug_pl;

        return $this;
    }

    public function getSlugEnNr(): ?int
    {
        return $this->slug_en_nr;
    }

    public function setSlugEnNr(?int $slug_en_nr): self
    {
        $this->slug_en_nr = $slug_en_nr;

        return $this;
    }

    public function getSlugDeNr(): ?int
    {
        return $this->slug_de_nr;
    }

    public function setSlugDeNr(?int $slug_de_nr): self
    {
        $this->slug_de_nr = $slug_de_nr;

        return $this;
    }

    public function getSlugPlNr(): ?int
    {
        return $this->slug_pl_nr;
    }

    public function setSlugPlNr(?int $slug_pl_nr): self
    {
        $this->slug_pl_nr = $slug_pl_nr;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name_pl' => $this->name_pl,
            'name_de' => $this->name_de,
            'name_en' => $this->name_en,
            'find' => $this->find,
            'company' => $this->company,
            'brand' => $this->brand,
            'ingredients' => $this->ingredients,
            'allergens' => $this->allergens,
            'country' => $this->country,
            'active' => $this->active,
            'barcode' => $this->barcode,
            'dosage_strength_pl' => $this->dosage_strength_pl,
            'dosage_strength_de' => $this->dosage_strength_de,
            'dosage_strength_en' => $this->dosage_strength_en,
            'form_pl' => $this->form_pl,
            'form_de' => $this->form_de,
            'form_en' => $this->form_en,
            'pack_size_pl' => $this->pack_size_pl,
            'pack_size_de' => $this->pack_size_de,
            'pack_size_en' => $this->pack_size_en,
            'atc_code' => $this->atc_code,
            'usage_category_pl' => $this->usage_category_pl,
            'usage_category_de' => $this->usage_category_de,
            'usage_category_en' => $this->usage_category_en,
            'side_effects_pl' => $this->side_effects_pl,
            'side_effects_de' => $this->side_effects_de,
            'side_effects_en' => $this->side_effects_en,
            'contraindications_pl' => $this->contraindications_pl,
            'contraindications_de' => $this->contraindications_de,
            'contraindications_en' => $this->contraindications_en,
            'brand_country' => $this->brand_country,
            'manufacturing_country' => $this->manufacturing_country,
            'categorized' => $this->categorized,
            'tagged' => $this->tagged,
            'slug_en' => $this->slug_en,
            'slug_de' => $this->slug_de,
            'slug_pl' => $this->slug_pl,
            'slug_en_nr' => $this->slug_en_nr,
            'slug_de_nr' => $this->slug_de_nr,
            'slug_pl_nr' => $this->slug_pl_nr,
        ];
    }
}
