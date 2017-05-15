<?php

namespace Flagrow\Masquerade\Api\Controllers;

use Flagrow\Masquerade\Api\Serializers\FieldSerializer;
use Flagrow\Masquerade\Field;
use Flagrow\Masquerade\Validators\FieldValidator;
use Flarum\Api\Controller\AbstractResourceController;
use Flarum\Core\Access\AssertPermissionTrait;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class SaveFieldController extends AbstractResourceController
{
    use AssertPermissionTrait;

    public $serializer = FieldSerializer::class;
    /**
     * @var FieldValidator
     */
    private $validator;

    public function __construct(FieldValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Get the data to be serialized and assigned to the response document.
     *
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return mixed
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $id = Arr::get($request->getQueryParams(), 'id');

        if ($id) {
            $attributes = Arr::get($request->getParsedBody(), 'attributes', []);
        } else {
            $attributes = $request->getParsedBody();
        }

        $this->validator->assertValid($attributes);

        if ($id) {
            $field = Field::findOrFail($id);
        } else {
            $field = new Field();
            $field->sort = $this->highestSort();
        }

        foreach (Arr::except($attributes, ['id', 'sort']) as $attribute => $value) {
            $field->{$attribute} = $value;
        }

        $field->save();

        return $field;
    }

    /**
     * @return int
     */
    protected function highestSort() {
        $max = Field::orderBy('sort', 'desc')->first();

        return $max ? $max->sort + 1: 0;
    }
}
