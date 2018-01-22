<?php

namespace App\Presenters;

use App\Model\TalkManager;
use App\Orm\Orm;
use App\Orm\Talk;
use App\Orm\TalkRepository;
use Nette\Utils\Json;
use Nextras\Orm\Collection\ICollection;

class ConferencePresenter extends BasePresenter
{
    /** @var TalkRepository $talkRepository */
    private $talkRepository;
    /**
     * @var TalkManager
     */
    private $talkManager;


    /**
     * ConferencePresenter constructor.
     * @param Orm $orm
     * @param TalkManager $talkManager
     */
    public function __construct(Orm $orm, TalkManager $talkManager)
    {
        $this->talkRepository = $orm->talk;
        $this->talkManager = $talkManager;
    }


    public function renderTalks()
    {
        /** @var ICollection|Talk[] $talks */
        $talks = $this->talkRepository->findAll();
        $categories = $this->talkManager->getCategories();

        $filtered = [];
        foreach ($talks as $talk) {
            if ($talk->conferee === null) {
                continue;
            }

            $extended = [];

            if ($talk->extended) {
                $extended = Json::decode($talk->extended, Json::FORCE_ARRAY);
            }

            $filtered[] = [
                'talk' => $talk,
                'extended' => $extended,
                'category' => isset($categories[$talk->category]) ? $categories[$talk->category] : null,
            ];
        }
        $this->template->talksInfo = $filtered;
        $this->template->count = count($filtered);
    }
}
