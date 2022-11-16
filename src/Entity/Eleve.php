<?php

namespace App\Entity;

use App\Repository\EleveRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Since;

/**
* @Hateoas\Relation(
* "self",
* href = @Hateoas\Route(
* "detailEleve",
* parameters = { "id" = "expr(object.getId())" }
* ),
* exclusion = @Hateoas\Exclusion(groups="getEleves")
* )
*
* @Hateoas\Relation(
* "delete",
* href = @Hateoas\Route(
* "deleteEleve",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getEleves", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
* @Hateoas\Relation(
* "update",
* href = @Hateoas\Route(
* "updateEleve",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getEleves", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
*/
#[ORM\Entity(repositoryClass: EleveRepository::class)]
class Eleve
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getEleves", "getProfesseurs", "getClasses" ])]
    #[Since("1.0")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getEleves", "getProfesseurs", "getClasses"])]
    #[Assert\NotBlank(message: "Le prénom de l'élève est obligatoire")]
    #[Assert\Length(
        min: 1, 
        max: 255, 
        minMessage: "Le prénom de l'élève doit faire au moins {{ limit }} caractères", 
        maxMessage: "Le prénom de l'élève ne peut pas faire plus de {{ limit }} caractères"
    )]
    #[Since("1.0")]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getEleves", "getProfesseurs", "getClasses"])]
    #[Assert\NotBlank(message: "Le nom de l'élève est obligatoire")]
    #[Assert\Length(
        min: 1, 
        max: 255, 
        minMessage: "Le nom de l'élève doit faire au moins {{ limit }} caractères", 
        maxMessage: "Le nom de l'élève ne peut pas faire plus de {{ limit }} caractères"
    )]
    #[Since("1.0")]
    private ?string $nom = null;

    #[ORM\Column]
    #[Groups(["getEleves", "getProfesseurs", "getClasses"])]
    #[Assert\NotBlank(message: "La moyenne de l'élève est obligatoire")]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: 'La moyenne doit être comprise entre {{ min }} et {{ max }}',
    )]
    #[Since("1.0")]
    private ?float $moyenne = null;

    #[ORM\ManyToOne(inversedBy: 'eleves')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getEleves"])]
    #[Since("1.0")]
    private ?Professeur $professeur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getMoyenne(): ?float
    {
        return $this->moyenne;
    }

    public function setMoyenne(float $moyenne): self
    {
        $this->moyenne = $moyenne;

        return $this;
    }

    public function getProfesseur(): ?Professeur
    {
        return $this->professeur;
    }

    public function setProfesseur(?Professeur $professeur): self
    {
        $this->professeur = $professeur;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }
}
