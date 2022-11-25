<?php

namespace App\Controller;

use App\Entity\Eleve;
use OpenApi\Annotations as OA;
use App\Service\VersioningService;
use App\Repository\EleveRepository;
use JMS\Serializer\SerializerInterface;
use App\Repository\ProfesseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Security\Core\Security as SecurityCore;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EleveController extends AbstractController
{
    /**
    * Cette méthode permet de récupérer l'ensemble des élèves.
    *
    * @OA\Response(
    * response=200,
    * description="Retourne la liste des élèves",
    * @OA\JsonContent(
    * type="array",
    * @OA\Items(ref=@Model(type=Eleve::class,
    * groups={"getEleves"}))
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
    * @OA\Tag(name="Eleve")
    *
    * @param Request $request
    * @param EleveRepository $eleveRepository
    * @param SerializerInterface $serializer
    * @param TagAwareCacheInterface $cachePool
    * @param SecurityCore $security
    * @param VersioningService $versioningService
    * @return JsonResponse
    */
    #[Route('/api/eleves', name: 'eleves', methods: ['GET'])]
    public function getEleveList(Request $request, EleveRepository $eleveRepository, SerializerInterface $serializer, TagAwareCacheInterface $cachePool, SecurityCore $security, VersioningService $versioningService): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getEleves-" . $page . "-" . $limit."-".implode(',', $security->getUser()->getRoles());
        $jsonEleveList = $cachePool->get($idCache, function(ItemInterface $item) use ($eleveRepository, $page, $limit, $serializer, $versioningService) {
            $item->tag("elevesCache");
            $eleveList = $eleveRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(['getEleves']);
            $version = $versioningService->getVersion();
            $context->setVersion($version);
            return $serializer->serialize($eleveList, 'json', $context);
        });
        return new JsonResponse($jsonEleveList, Response::HTTP_OK, [], true);

    }

    /**
     * Cette méthode permet de récupérer un seul élève selon son id.
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne l'élève demandé",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Eleve::class, groups={"getEleves"}))
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id de l'élève que l'on veut retourner",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Eleve")
     * 
     * @param Eleve $eleve
     * @param SerializerInterface $serializer
     * @param VersioningService $versioningService
     * @return JsonResponse
     */
    #[Route('/api/eleves/{id}', name: 'detailEleve', methods:['GET'])]
    public function getDetailEleve(Eleve $eleve, SerializerInterface $serializer, VersioningService $versioningService): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['getEleves']);
        $version = $versioningService->getVersion();
        $context->setVersion($version);
        $jsonEleve = $serializer->serialize($eleve, 'json', $context);
        return new JsonResponse($jsonEleve, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode permet de supprimer un seul élève selon son id.
     * 
     * @OA\Response(
     *      response=204,
     *      description="Supprime l'élève demandé",
     *      @OA\JsonContent(
     *          type="array",
     *         @OA\Items(type="boolean")
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id de l'élève que l'on veut supprimer",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Eleve")
     * 
     * @param Eleve $eleve
     * @param EntityManagerInterface $em
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('api/eleves/{id}', name: 'deleteEleve', methods:['DELETE'])]
    public function deleteEleve(Eleve $eleve, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse{
        $cachePool->invalidateTags(["elevesCache"]);
        $em->remove($eleve);
        $em->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
        * Cette méthode permet de créer un élève.
        *
        * @OA\Response(
        *   response=201,
        *   description="Retourne l'élève créer",
        *   @OA\JsonContent(
        *       type="array",
        *       @OA\Items(ref=@Model(type=Eleve::class,
        *       groups={"getEleves"}))
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
        *             "prenom": "John",
        *             "nom": "Doe",
        *             "moyenne": 13.0,
        *             "idProfesseur": 54
        *         },
        *         @OA\Schema (
        *              type="object",
        *              @OA\Property(property="prenom", required=true, description="Prénom de l'élève", type="string"),
        *              @OA\Property(property="nom", required=true, description="Nom de l'élève", type="string"),
        *              @OA\Property(property="moyenne", required=true, description="Moyenne de l'élève", type="number"),
        *              @OA\Property(property="idProfesseur", required=false, description="L'identifiant du professeur de l'élève", type="integer"),
        *         )
        *     )
        * )
        * @OA\Tag(name="Eleve")
        *
        * @param Request $request
        * @param SerializerInterface $serializer
        * @param EntityManagerInterface $em
        * @param UrlGeneratorInterface $urlGenerator
        * @param ProfesseurRepository $professeurRepository
        * @param ValidatorInterface $validator
        * @param VersioningService $versioningService
        * @return JsonResponse
    */
    #[Route('/api/eleves', name:"createEleve", methods: ['POST'])]
    public function createEleve(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ProfesseurRepository $professeurRepository, ValidatorInterface $validator, VersioningService $versioningService): JsonResponse
    {
        $eleve = $serializer->deserialize($request->getContent(), Eleve::class, 'json');

        // Récupération de l'ensemble des données envoyées sous forme de tableau
        $content = $request->toArray();

        if (gettype($content['idProfesseur']) == "integer") {
            $eleve->setProfesseur($professeurRepository->find($content['idProfesseur']));
        }

        // On vérifie les erreurs
        $errors = $validator->validate($eleve);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($eleve);
        $em->flush();
        $context = SerializationContext::create()->setGroups(['getEleves']);
        $version = $versioningService->getVersion();
        $context->setVersion($version);
        $jsonEleve = $serializer->serialize($eleve, 'json', $context);
        $location = $urlGenerator->generate('detailEleve', ['id' => $eleve->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonEleve, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
    * Cette méthode permet de modifier un élève selon son id.
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
    *      description="L'id de l'élève que l'on veut modifier",
    *      @OA\Schema(type="string")
    * )
    * @OA\RequestBody(
    *     required=true,
    *     @OA\JsonContent(
    *         example={
    *             "nom": "Dugardin",
    *             "prenom":"Jean",
    *             "moyenne":20,
    *             "idProfesseur":102
    *         },
    *         @OA\Schema (
    *              type="object",
    *              @OA\Property(property="nom", required=false, description="Nom de l'élève", type="string"),
    *              @OA\Property(property="prenom", required=false, description="Prénom de l'élève", type="string"),
    *              @OA\Property(property="moyenne", required=false, description="Moyenne de l'élève", type="float"),
    *              @OA\Property(property="idProfesseur", required=false, description="L'identifiant du professeur de l'élève", type="integer"),
    *         )
    *     )
    * )
    * @OA\Tag(name="Eleve")
    *
    * @param Request $request
    * @param SerializerInterface $serializer
    * @param Eleve $currentEleve
    * @param EntityManagerInterface $em
    * @param ProfesseurRepository $profRepo
    * @param ValidatorInterface $validator
    * @param TagAwareCacheInterface $cachePool
    * @return JsonResponse
    */
    #[Route('api/eleves/{id}', name: 'updateEleve', methods:['PUT'])]
    public function updateEleve(Request $request, SerializerInterface $serializer, Eleve $currentEleve, EntityManagerInterface $em, ProfesseurRepository $profRepo, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse{
        $newEleve = $serializer->deserialize($request->getContent(), Eleve::class, 'json');
        if ($newEleve->getNom()){
            $currentEleve->setNom($newEleve->getNom());
        }
        if ($newEleve->getPrenom()){
            $currentEleve->setPrenom($newEleve->getPrenom());
        }
        if ($newEleve->getMoyenne()){
            $currentEleve->setMoyenne($newEleve->getMoyenne());
        }

        $content = $request->toArray();
        if (isset($content['idProfesseur'])){
            $idProf = $content['idProfesseur'] ?? -1;
            $prof = $profRepo->find($idProf);
            $prof->addElefe($currentEleve);
        }

        $errors = $validator->validate($currentEleve);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $em->persist($currentEleve);
        $em->flush();

        // On vide le cache.
        $cachePool->invalidateTags(["elevesCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

}
