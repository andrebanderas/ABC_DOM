<?php
class ABC_Dom_Node extends ABC_Dom_Abstract
{
    public $tagName;
	public $nodeType;
	public $textContent = NULL;
    public $attributes;
	
    protected $_root;
	protected $_document;

    public function __construct($node, $doc = null)
    {
    	if($doc)
    		$this->_document = $doc;
		
        return $this->setNode($node);
    }
	
	public function __call($method, $arguments)
    {
        //Main Methods		
        if (!method_exists($this, $method))
        {
        	$list = new ABC_Dom_Elementlist;
			$list->setDocument($this->_document);
			
			$list[] = $this;
			
			if (!$arguments)
            	return $list->$method();
			else
			{
				if (count($arguments) == 1)
                    return $list->$method($arguments[0]);
                else
                {
                    $buffer = array();
                    for ($i = 0; $i < $n = count($arguments);)
                        $buffer[$arguments[$i++]] = $arguments[$i++];

                    return $list->$method($buffer);
                }
			}
		}
	}

    /** Set/Get * */
    public function setRoot($node)
    {
        $this->_root = $node;
    }

    public function getRoot()
    {
        return $this->_root;
    }
	
	public function setDocument($document)
	{
		$this->_document = $document;
	}
	
	public function getDocument()
	{
		return $this->_document;
	}

    public function setNode($node)
    {
        if ($node && $node->nodeType == 1)
        {
            $this->tagName = $node->tagName;
			$this->nodeType = 1;
            $this->_root = $node;

            $this->attributes = array();

            foreach ($this->_root->attributes as $attribute => $item)
                $this->attributes[$attribute] = $item->value;

            $this->attributes['html'] = self::getHtml();
        }
        elseif ($node && $node->nodeType == 3)
        {
            $this->_root = $node;
			$this->nodeType = 3;
            $this->attributes['html'] = ($text = trim($node->textContent)) ? $text : null;
        }
        elseif ($node && $node->nodeType == 8)
        {
            $this->_root = $node;
        }
        else
            return null;
    }

    public function getNodeType()
    {
        if ($this->root)
            return $this->root->nodeType;
    }

    public function getHtml()
    {
        $doc = new DOMDocument();

        $childs = $this->_root->childNodes;

        if ($childs->length > 0)
            foreach ($childs as $child)
                $doc->appendChild($doc->importNode($child, true));
	
        return trim($doc->saveHTML());
    }

    public function setHtml($content, $document = NULL)
    {
    	try
    	{
	        if (strpos($content, '<') !== false)
	        {
		        if (!$document)
		        	$document = $this->_document;
				
		        self::clearChilds($this->root);
			   	
				$doc = new DOMDocument;
	            @$doc->loadHTML($content);
	            
	            foreach ($doc->getElementsByTagName('body')->item(0)->childNodes as $element)
	            {
	                $append = new ABC_Dom_Node($element);
	                $this->insertBefore($append->cloneNode(true), NULL, $document);
	            }
	        }
	        else
	        {
	           $this->_root->nodeValue = $content;
	        }
	        
	        $this->attributes['html'] = $this->getHtml();
		}
		catch(Exception $e)
		{
			var_dump($e);
		}
    }

    private function clearChilds($node)
    {
        while ($node->hasChildNodes())
            $node->removeChild($node->firstChild);
    }

    public function setAttribute($attribute, $value)
    {
        $this->_root->setAttribute($attribute, $value);
        $this->setNode($this->_root);
    }

    public function getAttribute($attribute)
    {
        try
        {
            if (isset($this->attributes[$attribute]))
                return $this->attributes[$attribute];
            else
                return null;
        }
        catch (Exception $e)
        {
            print $e->getMessage();
        }
    }
	
	public function hasAttribute($attribute)
	{
		return isset($this->attributes[$attribute]);
	}

    public function getFirstChild()
    {
        $node = $this->root->firstChild;

        while ($node && $node->nodeType != 1)
            $node = $node->nextSibling;

        return new ABC_Dom_Node($node);
    }

    public function getLastChild()
    {
        $node = $this->root->lastChild;

        while ($node && $node->nodeType != 1)
            $node = $node->previousSibling;

        return new ABC_Dom_Node($node);
    }

    public function getChildNodes($textnodes = false)
    {
        $list = new ABC_Dom_Elementlist;
		$list->setDocument($this->_document);

        foreach ($this->root->childNodes as $element)
            if ($element->nodeType == '1' || $textnodes)
                $list[] = new ABC_Dom_Node($element);

        return $list;
    }

    public function getNextSibling()
    {
        $node = $this->root;

        do
        {
            $node = $node->nextSibling;
        }
        while ($node && $node->nodeType != 1);

        if (!$node)
            return null;

        return new ABC_Dom_Node($node);
    }

    public function getPreviousSibling()
    {
        $node = $this->root;

        do
        {
            $node = $node->previousSibling;
        }
        while ($node && $node->nodeType != 1);

        if (!$node)
            return null;

        return new ABC_Dom_Node($node);
    }

    public function getAllNextSiblings()
    {
        $list = new ABC_Dom_Elementlist;

        $node = $this->root;

        do
        {
            $node = $node->nextSibling;

            if ($node && $node->nodeType == 1)
                $list[] = new ABC_Dom_Node($node);
        }
        while ($node);

        return $list;
    }

    public function getParentNode()
    {
        $node = $this->root->parentNode;

        if ($node)
            return new ABC_Dom_Node($node);
        else
            return NULL;
    }

    public function setTextContent($text)
    {
        $this->root->textContent = $text;
    }

    /** Public Members * */

    /**
     * @param string $tagName
     * @return ABC_Dom_Nodelist
     */
    public function getElementsByTagName($tagName)
    {
        $list = new ABC_Dom_Elementlist;

        foreach ($this->root->getElementsByTagName($tagName) as $element)
            $list[] = new ABC_Dom_Node($element);

        return $list;
    }

    public function cloneNode($deep = false)
    {
        $node = ($deep) ? $this->root->cloneNode(true) : $this->root->cloneNode();

        return new ABC_Dom_Node($node);
    }

    public function insertBefore(ABC_Dom_Node $newnode, ABC_Dom_Node $refnode = NULL, ABC_Dom_Document $doc = NULL)
    {
        if ($doc)
            $newnode = $doc->importNode($newnode, true);

        if ($refnode)
            $node = $this->root->insertBefore($newnode->root, $refnode->root);
        else
            $node = $this->root->insertBefore($newnode->root);

        return new ABC_Dom_Node($node);
    }

    public function hasChildNodes()
    {
        return $this->root->hasChildNodes();
    }

    public function removeChild($node)
    {
        if ($node->root)
        {
            $node = $this->root->removeChild($node->root);
            return new ABC_Dom_Node($node);
        }
        else
        {
            return null;
        }
    }

    public function removeAttribute($attribute)
    {
        $this->root->removeAttribute($attribute);
    }
	
/*
    public function _attr($attr, $value = NULL)
    {
        if ($value !== NULL)
        {
            $this->setAttribute($attr, $value);
            return $this;
        }
        else
        {
            return $this->getAttribute($attr);
        }
    }
	
	public function _html($value = NULL)
    {
        if ($value !== NULL)
        {
            $this->setHtml($value);
            return $this;
        }
        else
        {
            return $this->getHtml();
        }
    }
*/
}