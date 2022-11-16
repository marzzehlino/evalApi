<?php

namespace App\Controller;

use App\Entity\Professeur;
use App\Repository\EleveRepository;
use App\Repository\ClasseRepository;
use JMS\Serializer\SerializerInterface;
use App\Repository\ProfesseurRepository;
use App\Service\VersioningService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Security\Core\Security as SecurityCore;

class ProfesseurController extends AbstractController
{
    /**
    * Cette méthode permet de récupérer l'ensemble des professeurs.
    *
    * @OA\Response(
    * response=200,
    * description="Retourne la liste des professeurs",
    * @OA\JsonContent(
    * type="array",
    * @OA\Items(ref=@Model(type=Professeur::class,
    * groups={"getProfesseurs"}))
    * )
    * )
    * @OA\Parameter(
    * name="page",
    * in="query",
    * description="La page que l'on veut récupérer",
    * @OA\Schema(type="int")
    * )
    *
    * @OA\Parameter(
    * name="limit",
    * in="query",
    * description="Le nombre d'éléments que l'on veut récupérer",
    * @OA\Schema(type="int")
    * )
    * @OA\Tag(name="Professeur")
    *
    * @param Request $request
    * @param ProfesseurRepository $profRepository
    * @param SerializerInterface $serializer
    * @param TagAwareCacheInterface $cachePool
    * @param SecurityCore $security
    * @param VersioningService $versioningService
    * @return JsonResponse
    */
    #[Route('/api/professeurs', name: 'professeurs', methods: ['GET'])]
    public function getProfesseurList(Request $request, ProfesseurRepository $profRepository, SerializerInterface $serializer, TagAwareCacheInterface $cachePool, SecurityCore $security, VersioningService $versioningService): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getProfesseurs-" . $page . "-" . $limit."-".implode(',', $security->getUser()->getRoles()); // Identifiant du cache
        $jsonProfList = $cachePool->get($idCache, function(ItemInterface $item) use ($profRepository, $page, $limit, $serializer, $versioningService) {
            $item->tag("professeursCache");
            $profList = $profRepository->findAllWithPagination($page, $limit); // On récupère la liste des professeurs en fonction de la pagination
            $context = SerializationContext::create()->setGroups(['getProfesseurs']); // On définit le groupe
            $version = $versioningService->getVersion(); // On récupère la version
            $context->setVersion($version); // On définit la version dans le contexte de serialization
            return $serializer->serialize($profList, 'json', $context);
        });
        return new JsonResponse($jsonProfList, Response::HTTP_OK, [], true); // On renvoie la listes des professeurs

    }

    /**
     * Cette méthode permet de récupérer un seul professeur(e) selon son id.
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne le professeur(e) demandé",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Professeur::class, groups={"getProfesseurs"}))
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id du professeur(e) que l'on veut retourner",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Professeur")
     * 
     * @param Professeur $professeur
     * @param SerializerInterface $serializer
     * @param VersioningService $versioningService
     * @return JsonResponse
     */
    #[Route('/api/professeurs/{id}', name: 'detailProfesseur', methods:['GET'])]
    public function getDetailProf(Professeur $professeur, SerializerInterface $serializer, VersioningService $versioningService): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['getProfesseurs']); // On défini le groupe
        $version = $versioningService->getVersion(); // On récupère la version
        $context->setVersion($version); // On défini la version
        $jsonProf = $serializer->serialize($professeur, 'json', $context); // On sérialize le professeur correspondant en fonction de la version d'API

        return new JsonResponse($jsonProf, Response::HTTP_OK, [], true); // On retourne le contenu du professeur
    }

    /**
     * Cette méthode permet de supprimer un seul professeur(e) selon son id.
     * 
     * @OA\Response(
     *      response=204,
     *      description="Supprime le Professeur(e) demandé",
     *      @OA\JsonContent(
     *          type="array",
     *         @OA\Items(type="boolean")
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id du Professeur(e) que l'on veut supprimer",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Professeur")
     *
     * @param Professeur $professeur
     * @param EntityManagerInterface $em
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('api/professeurs/{id}', name: 'deleteProfesseur', methods:['DELETE'])]
    public function deleteProf(Professeur $professeur, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse{
        $cachePool->invalidateTags(["professeursCache"]);
        foreach ($professeur->getEleves() as $eleve) {
            $em->remove($eleve, true); // On supprime les élèves du professeurs avant de supprimer le professeur
        }
        $em->remove($professeur); // On supprime le professeur
        $em->flush(); // On enregistre nos modifications
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT); // On retourne en l'état : sans contenu (no_content)
    }

    /**
        * Cette méthode permet de créer un professeur.
        *
        * @OA\Response(
        *   response=201,
        *   description="Retourne le professeur créer",
        *   @OA\JsonContent(
        *       type="array",
        *       @OA\Items(ref=@Model(type=Professeur::class,
        *       groups={"getProfesseurs"}))
        *   )
        *  )
        *
        * @OA\Response(
        *   response=400,
        *   description="Mauvaise requête",
        *  )
        * 
        * @OA\RequestBody(
        *     required=true,
        *     @OA\JsonContent(
        *         example={
        *             "prenom": "Albert",
        *             "nom": "Maurice",
        *             "tabClasse": {56,57,58},
        *             "tabEleve": {59}
        *         },
        *         @OA\Schema (
        *              type="object",
        *              @OA\Property(property="prenom", required=true, description="Prénom du professeur", type="string"),
        *              @OA\Property(property="nom", required=true, description="Nom du professeur", type="string"),
        *              @OA\Property(property="tabEleve", required=true, description="Tableau des identifiants des élèves du professeur", type="array"),
        *              @OA\Property(property="tabClasse", required=true, description="Tableau des identifiants des classes du professeur", type="array"),
        *         )
        *     )
        * )
        * @OA\Tag(name="Professeur")
        * 
        * @param Request $request
        * @param SerializerInterface $serializer
        * @param EntityManagerInterface $em
        * @param UrlGeneratorInterface $urlGenerator
        * @param ClasseRepository $classeRepository
        * @param EleveRepository $eleveRepository
        * @param ValidatorInterface $validator
        * @param VersioningService $versioningService
        * @return JsonResponse
    */
    #[Route('/api/professeurs', name:"createProfesseur", methods: ['POST'])]
    public function createProf(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ClasseRepository $classeRepository, EleveRepository $eleveRepository, ValidatorInterface $validator, VersioningService $versioningService): JsonResponse
    {
        $professeur = $serializer->deserialize($request->getContent(), Professeur::class, 'json'); // On déserialize le contenu de la requête

        // Récupération de l'ensemble des données envoyées sous forme de tableau
        $content = $request->toArray();

        if (isset($content['tabClasse'])) {
            foreach ($content['tabClasse'] as $idClasse) {
                $classeEnt = $classeRepository->find($idClasse); // On récupère la classe correspondante
                $professeur->addClasse($classeEnt); // On ajoute la classe au professeur
            }
        }

        if (isset($content['tabEleve'])) {
            foreach ($content['tabEleve'] as $idEleve) {
                $eleveEnt = $eleveRepository->find($idEleve); // On récupère l'entité correspondante
                $professeur->addElefe($eleveEnt); // On ajoute l'élève au professeur
            }
        }

        // On vérifie les erreurs
        $errors = $validator->validate($professeur);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true); // On renvoi les erreurs
        }

        $em->persist($professeur); // On enregistre nos modifications
        $em->flush(); // On envoi nos modifications en BDD
        $context = SerializationContext::create()->setGroups(['getProfesseurs']); // On définit le groupe
        $version = $versioningService->getVersion(); // On récupère la version d'API
        $context->setVersion($version); // On définit dans le contexte de serialization la version de l'API
        $jsonProf = $serializer->serialize($professeur, 'json', $context); // On sérialize le professeur avec la version de l'API
        $location = $urlGenerator->generate('detailProfesseur', ['id' => $professeur->getId()], UrlGeneratorInterface::ABSOLUTE_URL); // On génère une URL pour avoir parcourir la route detailProfesseur avec l'ID du professeur créer
        return new JsonResponse($jsonProf, Response::HTTP_CREATED, ["Location" => $location], true); // On retourne le professeur avec l'état : créer (created) et on redirige vers l'url générée.
    }

    /**
    * Cette méthode permet de modifier un professeur(e) selon son id.
    *
    * @OA\Response(
    *   response=204,
    *   description="Pas de contenu",
    *  )
    *
    * @OA\Response(
    *   response=400,
    *   description="Mauvaise requête",
    *  )
    *
    *  @OA\Parameter(
    *      name="id",
    *      in="path",
    *      description="L'id du professeur(e) que l'on veut modifier",
    *      @OA\Schema(type="string")
    * )
    * @OA\RequestBody(
    *     required=true,
    *     @OA\JsonContent(
    *         example={
    *             "nom": "Lamy",
    *             "prenom": "Alexandra",
    *             "idClasse": {85},
    *             "idEleve": {67}
    *         },
    *         @OA\Schema (
    *              type="object",
    *              @OA\Property(property="nom", required=true, description="Nom du professeur(e)", type="string"),
    *              @OA\Property(property="prenom", required=true, description="Prénom du professeur(e)", type="string"),
    *              @OA\Property(property="tabClasse", required=false, description="L'identifiant de la classe du professeur(e)", type="array"),
    *              @OA\Property(property="tabEleve", required=false, description="L'identifiant de l'élève du professeur(e)", type="array")
    *         )
    *     )
    * )
    * @OA\Tag(name="Professeur")
    *
    * @param Request $request
    * @param SerializerInterface $serializer
    * @param Professeur $currentProf
    * @param EntityManagerInterface $em
    * @param ClasseRepository $classeRepository
    * @param EleveRepository $eleveRepository
    * @param ValidatorInterface $validator
    * @param TagAwareCacheInterface $cachePool
    * @return JsonResponse
    */
    #[Route('api/professeurs/{id}', name: 'updateProf', methods:['PUT'])]
    public function updateProf(Request $request, SerializerInterface $serializer, Professeur $currentProf, EntityManagerInterface $em, ClasseRepository $classeRepository, EleveRepository $eleveRepository, ValidatorInterface $validator,  TagAwareCacheInterface $cachePool): JsonResponse{
        $newProf = $serializer->deserialize($request->getContent(), Professeur::class, 'json');
        $currentProf->setNom($newProf->getNom()); // On défini le nom du professeur
        $currentProf->setPrenom($newProf->getPrenom()); // On défini le prénom du professeur

        // On récupère en tableau les éléments de la requête
        $content = $request->toArray();

        if (gettype($content['tabClasse']) == "array") {
            foreach ($content['tabClasse'] as $idClasse) {
                $currentProf->addClasse($classeRepository->find($idClasse)); // On ajoute la classe au professeur
            }
        }

        if (gettype($content['tabEleve']) == "array") {
            foreach ($content['tabEleve'] as $idEleve) {
                $currentProf->addElefe($eleveRepository->find($idEleve)); // On ajoute l'élève au professeur
            }
        }

        // On vérifie les erreurs
        $errors = $validator->validate($currentProf);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($currentProf);
        $em->flush();

        // On vide le cache.
        $cachePool->invalidateTags(["professeursCache"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
