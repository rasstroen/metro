<?php

class Jauthors_module extends JBaseModule {

        function process() {
                switch (trim($_POST['action'])) {
                        case 'add_relation':
                                $this->add_relation();
                                break;
                        case 'del_relation':
                                $this->del_relation();
                                break;
                }
        }

        function ca() {
                global $current_user;
                $current_user = new CurrentUser();
                if (!$current_user->authorized)
                        throw new Exception('au');
                return true;
        }

        function add_relation() {
                global $current_user;
                $this->ca();
                
                $id1 = isset($_POST['id']) ? (int) $_POST['id'] : false;
                $id2 = isset($_POST['author_id']) ? (int) $_POST['author_id'] : false;
                if ($id1 == $id2) {
                        $this->data['error'] = 'Онанизмъ';
                        return false;
                }
                $relation_type = isset($_POST['relation_type']) ? (int) $_POST['relation_type'] : false;
                $person1 = Persons::getInstance()->getByIdLoaded($id1);
                $person2 = Persons::getInstance()->getByIdLoaded($id2);
                /* @var $person2 Person */
                if (!$person2 || !$person1) {
                        $this->data['error'] = 'Нет такого автора';
                        return false;
                }
                if (!$id1 || !$id2 || !$relation_type)
                        throw new Exception('id or item_id or relation_type missed');
                if (PersonRelations::addRelation($id1, $id2, $relation_type)) {
                        $this->data['success'] = 1;
                        $this->data['item_id'] = $id2;
                        $this->data['relation_type'] = PersonRelations::$relation_types[$relation_type];
                        /* @var $book2 Book */
                        $this->data['title'] = $person2->getName();
			$search = Search::getInstance();
			/* @var $search Search */
			$search->setAuthorToFullUpdate($person2->id);
			$search->setAuthorToFullUpdate($person1->id);
                } else {
                        $this->data['success'] = 0;
                        $this->data['error'] = 'ошибочка: ' . PersonRelations::getLastError();
                }
        }

        function del_relation() {
                global $current_user;
                $this->ca();
                $id1 = isset($_POST['id']) ? (int) $_POST['id'] : false;
                $id2 = isset($_POST['item_id']) ? (int) $_POST['item_id'] : false;
                if (!$id1 || !$id2)
                        throw new Exception('id or item_id missed');
                if (PersonRelations::delRelation($id1, $id2)) {
                        $this->data['success'] = 1;
                        $this->data['item_id'] = $id2;
			$search = Search::getInstance();
			/* @var $search Search */
			$search->setAuthorToFullUpdate($id1);
			$search->setAuthorToFullUpdate($id2);
                } else {
                        $this->data['success'] = 0;
                        $this->data['error'] = 'ошибочка:' . PersonRelations::getLastError();
                }
        }

}