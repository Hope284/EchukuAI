<?php

namespace App\Http\Controllers\Api;

use App\Domains\Entity\Models\Entity;
use App\Http\Controllers\Controller;
use App\Models\Token;
use App\Support\Dzeva\DzevaModelCatalog;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    /**
     * Get all entities
     *
     * @OA\Get(
     *      path="/api/entity/list",
     *      operationId="getAllEntities",
     *      tags={"Entities"},
     *      summary="Get all entities",
     *      description="Get all entities with their tokens.",
     *      security={{ "bearerAuth": {} }},
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *              type="object",
     *              example=[
     *                   {
     *                       "id": 1,
     *                       "key": "ogbon",
     *                       "name": "Ọgbọ́n",
     *                       "capability": "Smart Chat & Reasoning",
     *                       "icon": "Brain",
     *                       "description": "Best for smart conversations, business reasoning, planning, explanations, customer replies, and general AI chat.",
     *                       "token_type": "word",
     *                       "is_selected": true,
     *                       "status": "enabled",
     *                       "tokens":
     *                           {
     *                               "id": 1,
     *                               "type": "word",
     *                               "entity_id": 1,
     *                               "cost_per_token": "1.00",
     *                               "created_at": "2024-06-13T14:17:40.000000Z",
     *                               "updated_at": "2024-06-13T14:17:40.000000Z"
     *                           }
     *                   },
     *               ],
     *          ),
     *      ),
     *
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     * )
     */
    public function getAllEntities(Request $request)
    {
        if (! $request->user()?->isAdmin()) {
            $publicEntityKeys = collect(DzevaModelCatalog::entityMap())
                ->map(static fn ($entity) => $entity->value)
                ->values()
                ->all();

            return response()->json(
                Entity::query()
                    ->with('tokens')
                    ->whereIn('key', $publicEntityKeys)
                    ->get()
                    ->map(static fn (Entity $entity): array => DzevaModelCatalog::publicEntityPayload($entity))
                    ->values()
            );
        }

        $entities = Entity::all();
        $tokens = Token::all();

        foreach ($entities as $entity) {
            $entity->token_type = $entity->key->tokenType();
            $entity->tokens = $tokens->where('entity_id', $entity->id)->first();
            $entity->key_name = $entity->key->keyAsString();
            $entity->key_value = $entity->key->valueAsString();
        }

        return response()->json($entities);
    }
}
