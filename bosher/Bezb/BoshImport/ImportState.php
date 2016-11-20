<?php
namespace Bezb\BoshImport;

class ImportState {
    const STATE_SECTION = 1;
    const STATE_PRODUCT = 2;
    const STATE_ASSOC = 3;
    const STATE_FINAL = 4;

    /**
     * @var int
     */
    protected $state = self::STATE_SECTION;

    /**
     * @var int
     */
    protected $productPosition = 0;

    /**
     * @var int
     */
    protected $assocPosition = 0;

    /**
     * @var int
     */
    protected $sectionCount = 0;

    /**
     * @var array
     */
    protected $messages = [];

    public function build() {
        $properties = get_object_vars($this);
        $propertyNames = array_keys($properties);

        foreach($propertyNames as $propertyName) {
            if(isset($_REQUEST[$propertyName])) {
                $this->$propertyName = $_REQUEST[$propertyName];
            }
        }
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return int
     */
    public function getProductPosition()
    {
        return $this->productPosition;
    }

    /**
     * @param int $productPosition
     */
    public function setProductPosition($productPosition)
    {
        $this->productPosition = $productPosition;
    }

    /**
     * @return int
     */
    public function getAssocPosition()
    {
        return $this->assocPosition;
    }

    /**
     * @param int $assocPosition
     */
    public function setAssocPosition($assocPosition)
    {
        $this->assocPosition = $assocPosition;
    }

    public function incrementProduct() {
        $this->productPosition++;
    }

    public function incrementAssoc() {
        $this->assocPosition++;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param array $messages
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    /**
     * @param $message
     */
    public function addMessage($message) {
       $this->messages[] = $message;
    }

    public function incrementSection() {
        $this->sectionCount++;
    }

    /**
     * @return int
     */
    public function getSectionCount()
    {
        return $this->sectionCount;
    }
}