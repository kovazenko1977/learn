<?php
namespace Sanatorium\Core\Helpers;

use Sanatorium\Core\Database\JsonStore;

class TextBlockManager {
    private $store;

    public function __construct(JsonStore $store) {
        $this->store = $store;
    }

    public function getAll() {
        return $this->store->findAll('text_blocks');
    }

    public function getBySlug($slug) {
        $blocks = $this->getAll();
        foreach ($blocks as $b) {
            if ($b['slug'] === $slug) return $b['content'];
        }
        return '';
    }

    public function save($data) {
        return $this->store->save('text_blocks', $data);
    }

    public function delete($id) {
        return $this->store->delete('text_blocks', $id);
    }
}
