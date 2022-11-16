<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Service\VersioningService;
use App\Repository\ClasseRepository;
use JMS\Serializer\SerializerInterface;
use App\Repository\ProfesseurRepository;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\Security\Core\Security as SecurityCore;

class ClasseController extends AbstractController
{
    /**
    * Cette méthode permet de récupérer l'ensemble des classes.
    *
    * @OA\Response(
    * response=200,
    * description="Retourne la liste des classes",
    * @OA\JsonContent(
    * type="array",
    * @OA\Items(ref=@Model(type=Classe::class,
    * groups={"getClasses"}))
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
    * @OA\Tag(name="Classe")
    *
    * @param Request $request
    * @param ClasseRepository $classeRepository
    * @param SerializerInterface $serializer
    * @param TagAwareCacheInterface $cachePool
    * @param SecurityCore $security
    * @param VersioningService $versioningService
    * @return JsonResponse
    */
    #[Route('/api/classes', name: 'classes', methods: ['GET'])]
    public function getClasseList(Request $request, ClasseRepository $classeRepository, SerializerInterface $serializer, TagAwareCacheInterface $cachePool, SecurityCore $security, VersioningService $versioningService): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getClasses-" . $page . "-" . $limit."-".implode(',', $security->getUser()->getRoles()); // Identifiant du cache
        $jsonClasseList = $cachePool->get($idCache, function(ItemInterface $item) use ($classeRepository, $page, $limit, $serializer, $versioningService) {
            $item->tag("classesCache");
            $classeList = $classeRepository->findAllWithPagination($page, $limit); // On récupère la liste de classe en fonction de la pagination
            $context = SerializationContext::create()->setGroups(['getClasses']); // On définit le groupe
            $version = $versioningService->getVersion(); // On récupère la version
            $context->setVersion($version); // On définit la version dans le contexte de serialization
            return $serializer->serialize($classeList, 'json', $context);
        });
        return new JsonResponse($jsonClasseList, Response::HTTP_OK, [], true); // On renvoie la liste classes

    }

    /**
     * Cette méthode permet de récupérer une seule classe selon son id.
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne la classe demandée",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Classe::class, groups={"getClasses"}))
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id de la classe que l'on veut retourner",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Classe")
     * 
     * @param Classe $classe
     * @param SerializerInterface $serializer
     * @param VersioningService $versioningService
     * @return JsonResponse
    */
    #[Route('/api/classes/{id}', name: 'detailClasse', methods:['GET'])]
    public function getDetailClasse(Classe $classe, SerializerInterface $serializer, VersioningService $versioningService): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['getClasses']);
        $version = $versioningService->getVersion();
        $context->setVersion($version);
        $jsonClasse = $serializer->serialize($classe, 'json', $context);
        return new JsonResponse($jsonClasse, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode permet de supprimer une seule classe selon son id.
     * 
     * @OA\Response(
     *      response=204,
     *      description="Supprime la classe demandée",
     *      @OA\JsonContent(
     *          type="array",
     *         @OA\Items(type="boolean")
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id de la classe que l'on veut supprimer",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Classe")
     * 
     * @param Classe $classe
     * @param EntityManagerInterface $em
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('api/classes/{id}', name: 'deleteClasse', methods:['DELETE'])]
    public function deleteClasse(Classe $classe, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse{
        $cachePool->invalidateTags(["classesCache"]);
        $em->remove($classe);
        $em->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
        * Cette méthode permet de créer une clase.
        *
        * @OA\Response(
        *   response=201,
        *   description="Retourne la classe créée",
        *   @OA\JsonContent(
        *       type="array",
        *       @OA\Items(ref=@Model(type=Classe::class,
        *       groups={"getClasses"}))
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
        *             "nom": "6ème",
        *             "idProf": 100
        *         },
        *         @OA\Schema (
        *              type="object",
        *              @OA\Property(property="nom", required=true, description="Nom de la classe", type="string"),
        *              @OA\Property(property="idProfesseur", required=true, description="L'identifiant du professeur de la classe", type="integer"),
        *         )
        *     )
        * )
        * @OA\Tag(name="Classe")
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
    #[Route('/api/classes', name:"createClasse", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une classe')]
    public function createClasse(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ProfesseurRepository $profRepo, ValidatorInterface $validator, VersioningService $versioningService): JsonResponse
    {
        $classe = $serializer->deserialize($request->getContent(), Classe::class, 'json');

        $content = $request->toArray();
        $idProf = $content['idProfesseur'] ?? -1;
        $classe->setProfesseur($profRepo->find($idProf));

        // On vérifie les erreurs
        $errors = $validator->validate($classe);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($classe);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getClasses']);
        $version = $versioningService->getVersion();
        $context->setVersion($version);
        $jsonClasse = $serializer->serialize($classe, 'json', $context);

        $location = $urlGenerator->generate('detailClasse', ['id' => $classe->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonClasse, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
    * Cette méthode permet de modifier une classe selon son id.
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
    *      description="L'id de la classe que l'on veut modifier",
    *      @OA\Schema(type="string")
    * )
    * @OA\RequestBody(
    *     required=true,
    *     @OA\JsonContent(
    *         example={
    *             "nom": "6ème",
    *             "idProf": 100
    *         },
    *         @OA\Schema (
    *              type="object",
    *              @OA\Property(property="nom", required=true, description="Nom de la classe", type="string"),
    *              @OA\Property(property="idProfesseur", required=false, description="L'identifiant du professeur de la classe", type="integer"),
    *         )
    *     )
    * )
    * @OA\Tag(name="Classe")
    *
    * @param Request $request
    * @param SerializerInterface $serializer
    * @param Classe $currentClasse
    * @param EntityManagerInterface $em
    * @param ProfesseurRepository $profRepo
    * @param ValidatorInterface $validator
    * @param TagAwareCacheInterface $cachePool
    * @return JsonResponse
    */
    #[Route('api/classes/{id}', name: 'updateClasse', methods:['PUT'])]
    public function updateClasse(Request $request, SerializerInterface $serializer, Classe $currentClasse, EntityManagerInterface $em, ProfesseurRepository $profRepo, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse{
        $newClasse = $serializer->deserialize($request->getContent(), Classe::class, 'json');
        $currentClasse->setNom($newClasse->getNom());

        $content = $request->toArray();
        if (isset($content['idProfesseur'])) {
            $idProf = $content['idProfesseur'] ?? -1;
            $currentClasse->setProfesseur($profRepo->find($idProf));
        }
        

        $errors = $validator->validate($currentClasse);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($currentClasse);
        $em->flush();

        // On vide le cache.
        $cachePool->invalidateTags(["classesCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

}
