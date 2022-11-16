<?php

namespace App\Entity;

use App\Repository\ProfesseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Since;

/**
* @Hateoas\Relation(
* "self",
* href = @Hateoas\Route(
* "detailProfesseur",
* parameters = { "id" = "expr(object.getId())" }
* ),
* exclusion = @Hateoas\Exclusion(groups="getProfesseurs")
* )
*
* @Hateoas\Relation(
* "delete",
* href = @Hateoas\Route(
* "deleteProfesseur",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getProfesseurs", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
* @Hateoas\Relation(
* "update",
* href = @Hateoas\Route(
* "updateProf",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getProfesseurs", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
*/
#[ORM\Entity(repositoryClass: ProfesseurRepository::class)]
class Professeur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getProfesseurs", "getEleves", "getClasses"])]
    #[Since("1.0")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getProfesseurs", "getEleves", "getClasses"])]
    #[Assert\NotBlank(message: "Le nom du professeur est obligatoire")]
    #[Assert\Length(
        min: 1, 
        max: 255, 
        minMessage: "Le nom du professeur doit faire au moins {{ limit }} caractères", 
        maxMessage: "Le nom du professeur ne peut pas faire plus de {{ limit }} caractères"
    )]
    #[Since("1.0")]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getProfesseurs", "getEleves", "getClasses"])]
    #[Assert\NotBlank(message: "Le prénom du professeur est obligatoire")]
    #[Assert\Length(
        min: 1, 
        max: 255, 
        minMessage: "Le prénom du professeur doit faire au moins {{ limit }} caractères", 
        maxMessage: "Le prénom du professeur ne peut pas faire plus de {{ limit }} caractères"
    )]
    #[Since("1.0")]
    private ?string $prenom = null;

    #[ORM\OneToMany(mappedBy: 'professeur', targetEntity: Classe::class, orphanRemoval: true)]
    #[Groups(["getProfesseurs", "getEleves"])]
    #[Assert\NotBlank(message: "La liste des classes doit être renseignée")]
    #[Since("1.0")]
    private Collection $classe;

    #[ORM\OneToMany(mappedBy: 'professeur', targetEntity: Eleve::class)]
    #[Groups(["getProfesseurs", "getClasses"])]
    #[Assert\NotBlank(message: "La liste des élèves doit être renseignée")]
    #[Since("1.0")]
    private Collection $eleves;

    public function __construct()
    {
        $this->classe = new ArrayCollection();
        $this->eleves = new ArrayCollection();
    }

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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * @return Collection<int, Classe>
     */
    public function getClasse(): Collection
    {
        return $this->classe;
    }

    public function addClasse(Classe $classe): self
    {
        if (!$this->classe->contains($classe)) {
            $this->classe->add($classe);
            $classe->setProfesseur($this);
        }

        return $this;
    }

    public function removeClasse(Classe $classe): self
    {
        if ($this->classe->removeElement($classe)) {
            // set the owning side to null (unless already changed)
            if ($classe->getProfesseur() === $this) {
                $classe->setProfesseur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Eleve>
     */
    public function getEleves(): Collection
    {
        return $this->eleves;
    }

    public function addElefe(Eleve $elefe): self
    {
        if (!$this->eleves->contains($elefe)) {
            $this->eleves->add($elefe);
            $elefe->setProfesseur($this);
        }

        return $this;
    }

    public function removeElefe(Eleve $elefe): self
    {
        if ($this->eleves->removeElement($elefe)) {
            // set the owning side to null (unless already changed)
            if ($elefe->getProfesseur() === $this) {
                $elefe->setProfesseur(null);
            }
        }

        return $this;
    }
}
