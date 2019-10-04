<?php
class AdminPage {
    private $base = array();
    private $parts = array();
    
    public function __construct() {
        $this->base = get_fragments('./include/base.html');
        $this->parts = get_fragments('./include/admin.html');
    }

    public function render() {
        print(replace(array(
            'title' => 'DSV:s uppladdningstjänst'
        ), $this->base['head']));
        print($this->parts['base']);
        $this->print_complete();
        $this->print_pending();
        $this->print_pruned();
        print($this->base['foot']);
    }

    private function print_complete() {
        $list = array(new Item('5f6805ea-906d-4f1f-a008-e5aa557eb4c6',
                               'Anna anderssons pass',
                               'complete'));
        $out = '';
        foreach($list as $item) {
            $out .= replace(array('name' => $item->get_description(),
                                  'link' => $item->get_url()),
                            $this->parts['dl_item']);
        }
        print(replace(array('items' => $out), $this->parts['completed']));
    }

    private function print_pending() {
        $list = array(new Item('303d0691-3776-467c-a90f-f50fb38609f5',
                               'Bengt Bengtsons arbetstillstånd',
                               'pending'));
        $out = '';
        foreach($list as $item) {
            $out .= replace(array('name' => $item->get_description(),
                                  'link' => $item->get_url()),
                            $this->parts['ul_item']);
        }
        print(replace(array('items' => $out), $this->parts['pending']));
    }

    private function print_pruned() {
        $list = array(new Item('b3ea6a19-6cee-4491-9783-0b055478ce3c',
                               'Per Perssons körkort',
                               'pruned'));
        $out = '';
        foreach($list as $item) {
            $out .= replace(array('name' => $item->get_description(),
                                  'link' => $item->get_url()),
                            $this->parts['old_link_item']);
        }
        print(replace(array('items' => $out), $this->parts['pruned']));
    }
}
?>
