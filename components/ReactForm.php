<?php

namespace Impala;

use Nette\Application\UI\ISignalReceiver,
    Nette\Application\UI\Control,
    Nette\ComponentModel\IComponent,
    Nette\Http\IRequest,
    Nette\InvalidStateException;

/** @author Lubomir Andrisek */
class ReactForm extends Control implements IReactFormFactory {

    /** @var array */
    private $data  = [];

    /** @var string */
    private $jsDir;

    /** @var IRequest */
    private $request;

    public function __construct(string $jsDir, IRequest $request) {
        $this->jsDir = $jsDir;
        $this->request = $request;
    }

    public function create(): IReactFormFactory {
        return $this;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
    }

    private function add(string $key, string $label, string $method, string $tag, array $attributes = []): IReactFormFactory {
        $attributes['id'] = $key;
        foreach($attributes as $attributeId => $attribute) {
            if(null === $attribute) {
                unset($attributes[$attributeId]);
            /** keep given order in javascript */    
            } elseif ('data' == $attributeId && is_array($attribute)) {
                foreach($attribute as $overwriteId => $overwrite) {
                    $attributes[$attributeId]['_' . $overwriteId] = $overwrite;
                    unset($attributes[$attributeId][$overwriteId]);
                }
            }
        }
        $this->data[$key] = ['Attributes' => $attributes,
                            'Label' => $label,
                            'Method' => $method,
                            'Tag' => $tag];
        return $this;
    }

    public function addAction(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['style']['marginLeft'] = '10px';
        return $this->add($key, $label, __FUNCTION__, 'a', $attributes);
    }

    public function addCheckbox(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['type'] = 'checkbox';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    public function addDateTime(string $key, string $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label, 'addDateTime', 'input', $attributes);
    }

    public function addEmpty(string $key, string $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    public function addGallery(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['style']['clear'] = 'both';
        if(!isset($attributes['crop']) || !isset($attributes['content']) || !isset($attributes['delete'])) {
            throw new InvalidStateException('Name and content attribute intended for delete button and proceed message were not set.');
        }
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    private function addHandlers(array $links): array {
        $handlers = [];
        $methods = array_flip(get_class_methods($this->getParent()));
        $calls = array_flip(get_class_methods($this));
        foreach($links as $link) {
            if($this instanceof ISignalReceiver && isset($calls['handle' . ucfirst($link)])) {
                $handlers[$link] = $this->link($link);
            } else if($this->getParent() instanceof ISignalReceiver && isset($methods['handle' . ucfirst($link)])) {
                $handlers[$link] = $this->getParent()->link($link);
            }
        }
        return $handlers;
    }

    public function addHidden(string $key, string $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    public function addMessage(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['style'] = ['display' => 'none'];
        return $this->add($key, $label, __FUNCTION__, 'div', $attributes);
    }

    public function addMultiSelect(string $key, string $label, array $attributes = []): IReactFormFactory {
        if(null == $attributes['value']) {
            $attributes['value'] = [];
        }
        return $this->add($key, $label, __FUNCTION__, 'select', $attributes);
    }

    public function addMultiUpload($key, $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    public function addRadioList(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['type'] = 'radio';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    public function addSubmit(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['type'] = 'submit';
        return $this->add($key, $label, __FUNCTION__, 'input', $attributes);
    }

    public function addSelect(string $key, string $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label, __FUNCTION__, 'select', $attributes);
    }

    public function addProgressBar(string $key, string $label = '',  array $attributes = []): IReactFormFactory {
        $attributes['width'] = 0;
        return $this->add($key . '-progress', $label, __FUNCTION__, 'div', $attributes);
    }

    public function addTitle(string $key, string $label, array $attributes): IReactFormFactory {
        return $this->add($key, $label,  __FUNCTION__, 'div', $attributes);
    }

    public function addText(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['type'] = isset($attributes['type']) ? $attributes['type'] : 'text';
        return $this->add($key, $label,__FUNCTION__, 'input', $this->class($attributes));
    }

    public function addTextArea(string $key, string $label, array $attributes = []): IReactFormFactory {
        $attributes['type'] = 'textarea';
        return $this->add($key, $label,__FUNCTION__, 'input', $attributes);
    }

    public function addUpload(string $key, string $label, array $attributes = []): IReactFormFactory {
        return $this->add($key, $label,__FUNCTION__, 'input', $attributes);
    }

    private function class(array $attributes): array {
        $attributes['className'] = isset($attributes['className']) ? $attributes['className'] : 'form-control';
        return $attributes;
    }

    public function getOffset(string $key): array {
        return $this->data[$key];
    }

    public function getData(): array {
        return $this->data;
    }

    public function getRequest(): IRequest {
        return $this->request;
    }

    public function isSignalled(): bool {
        return !empty($this->request->getUrl()->getQueryParameter('do'));
    }

    public function replace(string $key, array $component): IReactFormFactory {
        $this->data[$key] = $component;
        return $this;
    }

    public function unsetOffset(string $key): IReactFormFactory {
        unset($this->data[$key]);
        return $this;
    }

    public function render(...$args): void {
        $this->template->component = $this->getName();
        $this->template->data = json_encode(['row' => $this->data, 'validators' => []]);
        $this->template->js = $this->getPresenter()->template->basePath . '/' . $this->jsDir;
        $this->template->links = json_encode($this->addHandlers(['crop', 'delete', 'done', 'export', 'import', 'move', 'prepare', 'put', 'resize', 'run', 'save', 'submit', 'validate']));
        $this->template->setFile(__DIR__ . '/../templates/react.latte');
        $this->template->render();
    }

}

interface IReactFormFactory {

    public function create(): IReactFormFactory;

}
