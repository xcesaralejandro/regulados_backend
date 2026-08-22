<?php

namespace Tests\Unit\Models;

use App\Models\ContactRequest;
use App\Models\Event;
use App\Models\EventAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class ContactRequestTest extends TestCase
{
    public function test_visible_fields_are_the_expected(): void
    {
        // Prepare
        $model_keys = array_keys(ContactRequest::factory()->create()->toArray());
        $visible_keys = [
            'id',
            'sender_id',
            'receiver_id',
            'workflow_state',
            'created_at',
            'updated_at',
        ];
        // Assert
        sort($model_keys);
        sort($visible_keys);
        $this->assertEquals($visible_keys, $model_keys);
    }

    public function test_fields_defined_as_hidden_are_not_displayed(): void
    {
        // Prepare
        $model_keys = array_keys(ContactRequest::factory()->create()->toArray());
        $hidden_keys = ['deleted_by', 'deleted_at'];
        // Assert
        $this->assertEmpty(array_intersect($hidden_keys, $model_keys));
    }

    public function test_fillable_attributes_are_configured_correctly(): void
    {
        // Prepare
        $model = new ContactRequest();
        $expected = [
            'sender_id',
            'receiver_id',
            'workflow_state',
            'deleted_by',
        ];
        // Execute
        $fillable = $model->getFillable();
        // Assert
        sort($expected);
        sort($fillable);
        $this->assertEquals($expected, $fillable);
    }

    public function test_model_cast_created_at(): void
    {
        // Prepare
        ContactRequest::factory()->create();
        // Execute
        $contactRequest = ContactRequest::first()->makeVisible(['created_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $contactRequest['created_at']);
    }

    public function test_model_cast_updated_at(): void
    {
        // Prepare
        ContactRequest::factory()->create();
        // Execute
        $contactRequest = ContactRequest::first()->makeVisible(['updated_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $contactRequest['updated_at']);
    }

    public function test_model_cast_deleted_at(): void
    {
        // Prepare
        $contactRequest = ContactRequest::factory()->create();
        // Execute
        $contactRequest->delete();
        $deletedModel = ContactRequest::withTrashed()->find($contactRequest->id)->makeVisible(['deleted_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $deletedModel['deleted_at']);
    }

    public function test_model_has_sender_relationship(): void
    {
        // Prepare
        $sender = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create(['sender_id' => $sender->id]);
        // Execute
        $contactRequest = ContactRequest::with('sender')->find($contactRequest->id);
        // Assert
        $this->assertTrue($contactRequest->relationLoaded('sender'));
        $this->assertNotNull($contactRequest->sender);
        $this->assertEquals($sender->id, $contactRequest->sender->id);
    }

    public function test_model_has_sender_relationship_correctly_formed(): void
    {
        // Prepare
        $contactRequest = new ContactRequest();
        // Execute
        $relation = $contactRequest->sender();
        // Assert
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('sender_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_model_has_receiver_relationship(): void
    {
        // Prepare
        $receiver = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create(['receiver_id' => $receiver->id]);
        // Execute
        $contactRequest = ContactRequest::with('receiver')->find($contactRequest->id);
        // Assert
        $this->assertTrue($contactRequest->relationLoaded('receiver'));
        $this->assertNotNull($contactRequest->receiver);
        $this->assertEquals($receiver->id, $contactRequest->receiver->id);
    }

    public function test_model_has_receiver_relationship_correctly_formed(): void
    {
        // Prepare
        $contactRequest = new ContactRequest();
        // Execute
        $relation = $contactRequest->receiver();
        // Assert
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('receiver_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_model_has_deleter_relationship(): void
    {
        // Prepare
        $deleter = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create(['deleted_by' => $deleter->id]);
        // Execute
        $contactRequest = ContactRequest::with('deleter')->find($contactRequest->id);
        // Assert
        $this->assertTrue($contactRequest->relationLoaded('deleter'));
        $this->assertNotNull($contactRequest->deleter);
        $this->assertEquals($deleter->id, $contactRequest->deleter->id);
    }

    public function test_model_has_deleter_relationship_correctly_formed(): void
    {
        // Prepare
        $contactRequest = new ContactRequest();
        // Execute
        $relation = $contactRequest->deleter();
        // Assert
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('deleted_by', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_deleted_by_field_accepts_null(): void
    {
        // Prepare & Execute
        $contactRequest = ContactRequest::factory()->create(['deleted_by' => null]);
        // Assert
        $this->assertNull($contactRequest->deleted_by);
    }

    public function test_sender_id_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        ContactRequest::factory()->create(['sender_id' => null]);
    }

    public function test_receiver_id_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        ContactRequest::factory()->create(['receiver_id' => null]);
    }

    public function test_model_implements_soft_deletes(): void
    {
        // Prepare
        $contactRequest = ContactRequest::factory()->create();
        // Execute
        $contactRequest->delete();
        // Assert
        $this->assertSoftDeleted('contact_requests', ['id' => $contactRequest->id]);
        $this->assertNull(ContactRequest::find($contactRequest->id));
        $this->assertNotNull(ContactRequest::withTrashed()->find($contactRequest->id));
    }

    public function test_scope_between_users_returns_records_when_user_a_is_sender(): void
    {
        // Prepare
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $userA->id,
            'receiver_id' => $userB->id,
        ]);
        // Execute
        $results = ContactRequest::betweenUsers($userA->id, $userB->id)->get();
        // Assert
        $this->assertTrue($results->contains('id', $contactRequest->id));
    }

    public function test_scope_between_users_returns_records_when_user_b_is_sender(): void
    {
        // Prepare
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $userB->id,
            'receiver_id' => $userA->id,
        ]);
        // Execute
        $results = ContactRequest::betweenUsers($userA->id, $userB->id)->get();
        // Assert
        $this->assertTrue($results->contains('id', $contactRequest->id));
    }

    public function test_scope_between_users_filters_out_unrelated_records(): void
    {
        // Prepare
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userC = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $userA->id,
            'receiver_id' => $userC->id,
        ]);
        // Execute
        $results = ContactRequest::betweenUsers($userA->id, $userB->id)->get();
        // Assert
        $this->assertFalse($results->contains('id', $contactRequest->id));
    }

    public function test_scope_from_to_matches_exact_direction(): void
    {
        // Prepare
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $userA->id,
            'receiver_id' => $userB->id,
        ]);
        // Execute
        $results = ContactRequest::fromTo($userA->id, $userB->id)->get();
        // Assert
        $this->assertTrue($results->contains('id', $contactRequest->id));
    }

    public function test_scope_from_to_ignores_inverse_direction(): void
    {
        // Prepare
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $userB->id,
            'receiver_id' => $userA->id,
        ]);
        // Execute
        $results = ContactRequest::fromTo($userA->id, $userB->id)->get();
        // Assert
        $this->assertFalse($results->contains('id', $contactRequest->id));
    }
}
