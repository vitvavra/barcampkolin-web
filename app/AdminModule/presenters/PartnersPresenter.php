<?php

namespace App\AdminModule\Presenters;

use App\Model\PartnerLogoStorage;
use App\Model\PartnersManager;
use Nette\Application\UI\Form;
use Nette\Database\ForeignKeyConstraintViolationException;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;

/**
 * Class PartnersPresenter
 * @package App\AdminModule\Presenters
 */
class PartnersPresenter extends BasePresenter
{
    /**
     * PartnersPresenter constructor.
     * @param PartnersManager $partners
     * @param PartnerLogoStorage $storage
     */
    public function __construct(private readonly PartnersManager $partners, private readonly PartnerLogoStorage $storage)
    {
    }


    /**
     * @param null $id
     * @throws \Nette\Application\BadRequestException
     */
    public function renderPartner($id = null): void
    {
        $partner = $id !== null ? $this->partners->getPartnerById($id) : null;

        /** @var Form $form */
        $form = $this['partnerForm'];

        if ($partner) {
            $form->setDefaults($partner);
        }
    }


    /**
     * @param null $id
     * @throws \Nette\Application\BadRequestException
     */
    public function renderGroup($id = null): void
    {
        $group = $id !== null ? $this->partners->getGroupById($id) : null;

        /** @var Form $form */
        $form = $this['groupForm'];

        if ($group) {
            $form->setDefaults($group);
        }
    }


    /**
     *
     */
    public function renderDefault()
    {
    }


    /**
     * @return Form
     */
    public function createComponentPartnerForm(): \Nette\Application\UI\Form
    {
        $form = new Form();
        $form->addHidden('id');
        $form->addSelect('group_id', 'Skupina', $this->partners->getGroups(false)->fetchPairs('id', 'name'));
        $form->addText('name', 'Název')
            ->setOption('description', 'U obrázku jako titulek, bez obrázku jako HTML');
        $form->addText('url', 'URL')
            ->setRequired(false)
            ->addRule(Form::URL);
        $form->addUpload('picture', 'Obrázek')
            ->getControlPrototype()->addAttributes(['accept' => 'image/*'])
            ->setOption('description', 'Pokud při úpravě chcete obrázek ponechat původní, nevyplňujte');
        $form->addCheckbox('keep_picture', 'Ponechat obrázek')
            ->setDefaultValue(true)
            ->setOption('description', 'Zaškrtnutím se obrázek ponechá nebo uloží; zrušením zaškrtnutí se smaže');
        $form->addInteger('height', 'Výška obrázku')->setDefaultValue(100);
        $form->addCheckbox('enabled', 'Viditelný')
            ->setDefaultValue(true);
        $form->addSubmit('submit', 'Uložit');

        $form->addProtection();

        $form->onSuccess[] = $this->onPartnerFormSuccess(...);

        return $form;
    }


    /**
     * @param Form $form
     * @param ArrayHash $values
     * @throws \Nette\Application\AbortException
     */
    public function onPartnerFormSuccess(Form $form, ArrayHash $values): void
    {
        $id = $values->id ?: null;

        if ($values->picture instanceof FileUpload && $values->picture->isImage()) {
            $values->picture_url = $this->storage->saveUploaded($values->picture, $values->name);
        } elseif ($values->keep_picture === false) {
            $values->picture_url = null;
        }

        unset($values->id);
        unset($values->picture);
        unset($values->keep_picture);

        $this->partners->insertUpdatePartner($values, $id);

        $this->flashMessage("Uloženo", 'success');
        $this->redirect('default');
    }


    /**
     * @return DataGrid
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentPartnersDatagrid(): \Ublaboo\DataGrid\DataGrid
    {
        DataGrid::$iconPrefix = 'glyphicon glyphicon-';

        $grid = new DataGrid();

        $grid->addToolbarButton('partner', 'Nový partner')
            ->setClass('btn btn-xs btn-primary')
            ->setIcon('plus');

        $grid->setDataSource($this->partners->getPartners(false));

        $grid->setSortable(true);
        $grid->setSortableHandler('sortPartner!');

        $grid->addColumnLink('name', 'Jméno', 'partner');

        $groups = $this->partners->getGroups(false)->fetchPairs('id', 'name');
        $grid->addColumnText('group', 'Skupina')
            ->setRenderer(fn($item) => $groups[$item->group_id]);

        $grid->addColumnText('url', 'Odkaz')
            ->setRenderer(fn($item): string => $item->url ? 'Ano' : 'Ne');

        $grid->addColumnText('picture', 'Obrázek')
            ->setRenderer(fn($item): string => $item->picture_url ? 'Ano' : 'Ne');

        $grid->addColumnText('visible', 'Zobrazen')
            ->setRenderer(fn($item): string => $item->enabled ? 'Ano' : 'Ne');

        $grid->addAction('partner', '')
            ->setIcon('pencil')
            ->setTitle('Upravit');

        $grid->addAction('delete', '', 'deletePartner!')
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger ajax')
            ->setConfirmation(new StringConfirmation('Opravdu chcete smazat partnera %s?', 'name'));

        return $grid;
    }


    /**
     * @param $item_id
     * @param $prev_id
     * @param $next_id
     * @throws \Nette\Application\AbortException
     * @secured
     */
    public function handleSortPartner($item_id = null, $prev_id = null, $next_id = null): void
    {
        $item = $this->partners->getPartnerById($item_id);
        $prevItem = $prev_id ? $this->partners->getPartnerById($prev_id) : null;
        $nextItem = $next_id ? $this->partners->getPartnerById($next_id) : null;

        $this->partners->changePartnersOrder($item, $prevItem, $nextItem);

        $this->flashMessage("Partner " . $item->name . " byl přesunut", 'success');

        if ($this->isAjax()) {
            $this->redrawControl('flashes');
            $this['partnersDatagrid']->reload();
        } else {
            $this->redirect('this');
        }
    }


    /**
     * @param $id
     * @throws \Nette\Application\AbortException
     * @secured
     */
    public function handleDeletePartner($id): void
    {
        $partner = $this->partners->getPartnerById($id);

        $name = $partner->name;

        $this->partners->delete($partner);
        $this->flashMessage("Partner \"$name\" smazán", 'success');

        if ($this->isAjax()) {
            $this->redrawControl('flashes');
            $this['partnersDatagrid']->reload();
        } else {
            $this->redirect('this');
        }
    }


    /**
     * @return Form
     */
    public function createComponentGroupForm(): \Nette\Application\UI\Form
    {
        $form = new Form();
        $form->addHidden('id');
        $form->addText('name', 'Název')
            ->setOption('description', 'Povolené HTML');
        $form->addInteger('height', 'Výchozí výška obrázku')->setDefaultValue(null);
        $form->addCheckbox('enabled', 'Viditelný')
            ->setDefaultValue(true);
        $form->addSubmit('submit', 'Uložit');

        $form->addProtection();

        $form->onSuccess[] = $this->onGroupFormSuccess(...);

        return $form;
    }


    /**
     * @param Form $form
     * @param ArrayHash $values
     * @throws \App\Model\PartnerNotFound
     * @throws \Nette\Application\AbortException
     * @throws \Nette\InvalidStateException
     */
    public function onGroupFormSuccess(Form $form, ArrayHash $values): void
    {
        $id = $values->id ?: null;

        unset($values->id);


        $this->partners->insertUpdateGroup($values, $id);

        $this->flashMessage("Uloženo", 'success');
        $this->redirect('default');
    }


    /**
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentGroupsDatagrid(): \Ublaboo\DataGrid\DataGrid
    {
        DataGrid::$iconPrefix = 'glyphicon glyphicon-';

        $grid = new DataGrid();

        $grid->addToolbarButton('group', 'Nová skupina')
            ->setClass('btn btn-xs btn-primary')
            ->setIcon('plus');

        $grid->setDataSource($this->partners->getGroups(false));

        $grid->setSortable(true);
        $grid->setSortableHandler('sortGroup!');

        $grid->addColumnLink('name', 'Jméno', 'group');

        $grid->addColumnText('visible', 'Zobrazen')
            ->setRenderer(fn($item): string => $item->enabled ? 'Ano' : 'Ne');

        $grid->addAction('group', '')
            ->setIcon('pencil')
            ->setTitle('Upravit');

        $grid->addAction('delete', '', 'deleteGroup!')
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger ajax')
            ->setConfirmation(new StringConfirmation('Opravdu chcete smazat z skupinu %s?', 'name'));

        return $grid;
    }


    /**
     * @param int $item_id
     * @param int|NULL $prev_id
     * @param int|NULL $next_id
     * @return void
     * @throws \Nette\Application\AbortException
     * @secured
     */
    public function handleSortGroup($item_id = null, $prev_id = null, $next_id = null): void
    {
        $item = $this->partners->getGroupById($item_id);
        $prevItem = $prev_id ? $this->partners->getGroupById($prev_id) : null;
        $nextItem = $next_id ? $this->partners->getGroupById($next_id) : null;

        $this->partners->changeGroupsOrder($item, $prevItem, $nextItem);

        $this->flashMessage("Skupina " . $item->name . " byla přesunuta", 'success');

        if ($this->isAjax()) {
            $this->redrawControl('flashes');
            $this['groupsDatagrid']->reload();
        } else {
            $this->redirect('this');
        }
    }


    /**
     * @param $id
     * @throws \Nette\Application\AbortException
     * @secured
     */
    public function handleDeleteGroup($id): void
    {
        $group = $this->partners->getGroupById($id);

        $name = $group->name;

        try {
            $this->partners->delete($group);
            $this->flashMessage("Skupina \"$name\" smazána", 'success');
        } catch (ForeignKeyConstraintViolationException) {
            $this->flashMessage("Nelze smazat skupinu, ve které je nějaký partner", 'danger');
        }

        if ($this->isAjax()) {
            $this->redrawControl('flashes');
            $this['groupsDatagrid']->reload();
        } else {
            $this->redirect('this');
        }
    }
}
