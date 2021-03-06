<?php

namespace App\Entity\Framework;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class CfRubric
 *
 * @ORM\MappedSuperclass()
 *
 * @ORM\Table(name="rubric")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\CfRubricRepository")
 */
class CfRubric extends AbstractLsBase implements CaseApiInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", length=65535, nullable=true)
     *
     * @Serializer\Expose()
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     *
     * @Serializer\Expose()
     */
    private $description;

    /**
     * @var Collection|CfRubricCriterion[]
     *
     * @ORM\OneToMany(targetEntity="CfRubricCriterion", mappedBy="rubric")
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("CFRubricCriteria"),
     * @Serializer\Type("array<App\Entity\Framework\CfRubricCriterion>"),
     */
    private $criteria;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->criteria = new ArrayCollection();
        $this->criteriaLevels = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return CfRubric
     */
    public function setTitle($title): CfRubric
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return CfRubric
     */
    public function setDescription($description): CfRubric
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return CfRubricCriterion[]|Collection
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param CfRubricCriterion[]|Collection $criteria
     *
     * @return CfRubric
     */
    public function setCriteria($criteria): CfRubric
    {
        $this->criteria = $criteria;

        return $this;
    }
}
