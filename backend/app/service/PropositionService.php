<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Organisation;
use App\Entity\Proposition;
use App\Manager\PropositionManager;
use Exception;

class  PropositionService {

    private PropositionManager $propositionManager;
    private OrganisationService $organisationService;
    private UserService $userService;

    public function __construct(PropositionManager $propositionManager, OrganisationService $organisationService,  UserService $userService)
    {
        $this->propositionManager = $propositionManager;
        $this->organisationService = $organisationService;
        $this->userService = $userService;
    }

    public function createProposition(Organisation $org, User $author, string $title, string $description, string $tags) {
        // on créer une proposition
        $propositionId = $this->propositionManager->createProposition($org->getId(), $author->getId(), $title, $description);

        // créer une array avec la liste des tags : séparateur utiliser : virgule
        $tagsList = explode(",", $tags);

        // récupération de la liste de tag de l'organisation 
        $orgTags = $this->organisationService->getOrganisationTags($org);
        $orgTagsName = [];
        foreach ($orgTags as $orgTag) {
            array_push($orgTagsName, $orgTag->getName());
        }

        foreach ($tagsList as $tag) {
            // pour chaque tags vérifier si il éxiste deja dans la db pour l'organisation
            if (!in_array($tag, $orgTagsName)) {
                $this->organisationService->addTagToOrganisation($org, $tag);
            }
            $tagId = $this->organisationService->getTagId($org, $tag);
            $this->addTagToProposition($propositionId, $tagId);
        }
    }

    public function getPropositionById(int $id): Proposition {
        return $this->propositionManager->getPropositionById($id);
    }

    public function getPropositionByOrgId(int $orgId): array {
        return $this->propositionManager->getPropositionByOrgId($orgId);
    }

    public function addTagToProposition(int $propositionId, int $tagId) {
        $this->propositionManager->addTagToProposition($propositionId, $tagId);
    }


    /**
     * Permet de like une proposition
     */
    public function likeProposition(Proposition $proposition, User $author) {
        if ($this->isAlreadyDisliked($proposition, $author)) {
            throw new Exception('proposition-already-disliked');
        }

        if ($this->isAlreadyLiked($proposition, $author)) {
            $this->propositionManager->removeLikeProposition($proposition->getId(), $author->getId());
        }
        else {
            $this->propositionManager->likeProposition($proposition->getId(), $author->getId());
        }
    }

    /**
     * Permet de dislike une proposition
     */
    public function dislikeProposition(Proposition $proposition, User $author) {
        if ($this->isAlreadyLiked($proposition, $author)) {
            throw new Exception('proposition-already-liked');
        }

        if ($this->isAlreadyDisliked($proposition, $author)) {
            $this->propositionManager->removeLikeProposition($proposition->getId(), $author->getId());
        }
        else {
            $this->propositionManager->dislikeProposition($proposition->getId(), $author->getId());
        }
    }

    public function isAlreadyLiked(Proposition $proposition, User $author): bool {
        $likeList = $this->propositionManager->getLike($proposition->getId());

        foreach($likeList as $like) {
            if ($like["id_Utilisateur"] == $author->getId()) {
                return true;
            }
        }
        
        return false;
    }

    public function isAlreadyDisliked(Proposition $proposition, User $author): bool {
        $dislikeList = $this->propositionManager->getDislike($proposition->getId());

        foreach($dislikeList as $dislike) {
            if ($dislike["id_Utilisateur"] == $author->getId()) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Permet de récupéré la liste des utilisateurs ayant like une proposition
     * 
     * @return array La liste des objets utilisateurs qui ont liker la proposition
     */
    public function getLikers(Proposition $proposition): array {
        $likers = $this->propositionManager->getLike($proposition->getId());
    
        $return = [];
        foreach($likers as $liker) {
            $user = $this->userService->getUserById($liker["id_Utilisateur"]);
            array_push($return, $user);
        }

        return $return;
    }

    /**
     * Permet de récupéré la liste des utilisateurs ayant dislike une proposition
     * 
     * @return array La liste des objets utilisateurs qui ont disliker la proposition
     */
    public function getDislikers(Proposition $proposition): array {
        $dislikers = $this->propositionManager->getDislike($proposition->getId());

        $return = [];
        foreach($dislikers as $disliker) {
            $user = $this->userService->getUserById($disliker["id_Utilisateur"]);
            array_push($return, $user);
        }

        return $return;
    }
}
