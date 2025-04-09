<?php

namespace App\Service;

use App\Entity\Hashtag;
use App\Entity\Post;

class HashtagPostRelationManager
{
    public function addPostToHashtag(Hashtag $hashtag, Post $post): void
    {
        if (!$hashtag->getPosts()->contains($post)) {
            $hashtag->getPosts()->add($post);
            $post->getHashtags()->add($hashtag);
            $hashtag->incrementUsageCount();
        }
    }

    public function removePostFromHashtag(Hashtag $hashtag, Post $post): void
    {
        if ($hashtag->getPosts()->removeElement($post)) {
            $post->getHashtags()->removeElement($hashtag);
        }
    }
}
