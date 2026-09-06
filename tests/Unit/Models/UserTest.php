<?php

namespace Tests\Unit\Models;

use App\Models\ContactRequest;
use App\Models\CustomPivots\EventEnroll;
use App\Models\Event;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class UserTest extends TestCase
{

    public function test_visible_fields_are_the_expected(): void
    {
        // Prepare
        $user = User::factory()->create();
        $model_keys = array_keys($user->toArray());
        $visible_keys = [
            'id',
            'name',
            'surname',
            'semester',
            'email',
            'phone',
            'birthdate',
            'avatar',
            'instagram',
            'discord',
        ];
        // Assert
        sort($model_keys);
        sort($visible_keys);
        $this->assertEquals($visible_keys, $model_keys);
    }

    public function test_fields_defined_as_hidden_are_not_displayed(): void
    {
        // Prepare
        $model_keys = array_keys(User::factory()->create()->toArray());
        $hidden_keys = [
            'gender',
            'custom_gender',
            'program_id',
            'created_at',
            'updated_at',
            'deleted_at',
            'remember_token',
            'access_code',
            'access_code_expires_at',
            'preferred_start_time',
            'canvas_user_id'
        ];
        // Assert
        $this->assertEmpty(array_intersect($hidden_keys, $model_keys));
    }

    public function test_fillable_attributes_are_configured_correctly(): void
    {
        // Prepare
        $user = new User();
        $expected_fillable = [
            'name',
            'surname',
            'gender',
            'custom_gender',
            'semester',
            'email',
            'phone',
            'birthdate',
            'program_id',
            'avatar',
            'access_code',
            'access_code_expires_at',
            'canvas_user_id',
            'instagram',
            'preferred_start_time',
            'discord',
        ];
        // Execute
        $fillable = $user->getFillable();
        // Assert
        sort($expected_fillable);
        sort($fillable);
        $this->assertEquals($expected_fillable, $fillable);
    }

    public function test_model_cast_created_at(): void
    {
        // Prepare
        User::factory()->create();
        // Execute
        $user = User::first()->makeVisible(['created_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $user['created_at']);
    }

    public function test_model_cast_updated_at(): void
    {
        // Prepare
        User::factory()->create();
        // Execute
        $user = User::first()->makeVisible(['updated_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $user['updated_at']);
    }

    public function test_canvas_user_id_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['canvas_user_id' => null]);
        // Assert
        $this->assertNull($user->canvas_user_id);
    }

    public function test_program_id_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['program_id' => null]);
        // Assert
        $this->assertNull($user->program_id);
    }

    public function test_semester_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['semester' => null]);
        // Assert
        $this->assertNull($user->semester);
    }

    public function test_surname_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['surname' => null]);
        // Assert
        $this->assertNull($user->surname);
    }

    public function test_gender_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['gender' => null]);
        // Assert
        $this->assertNull($user->gender);
    }

    public function test_custom_gender_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['custom_gender' => null]);
        // Assert
        $this->assertNull($user->custom_gender);
    }

    public function test_email_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['email' => null]);
        // Assert
        $this->assertNull($user->email);
    }

    public function test_phone_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['phone' => null]);
        // Assert
        $this->assertNull($user->phone);
    }

    public function test_instagram_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['instagram' => null]);
        // Assert
        $this->assertNull($user->instagram);
    }

    public function test_discord_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['discord' => null]);
        // Assert
        $this->assertNull($user->discord);
    }

    public function test_birthdate_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['birthdate' => null]);
        // Assert
        $this->assertNull($user->birthdate);
    }

    public function test_avatar_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['avatar' => null]);
        // Assert
        $this->assertNull($user->avatar);
    }

    public function test_access_code_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['access_code' => null]);
        // Assert
        $this->assertNull($user->access_code);
    }

    public function test_access_code_expires_at_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['access_code_expires_at' => null]);
        // Assert
        $this->assertNull($user->access_code_expires_at);
    }

    public function test_remember_token_field_accepts_null(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['remember_token' => null]);
        // Assert
        $this->assertNull($user->remember_token);
    }

    public function test_name_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        User::factory()->create(['name' => null]);
    }

    public function test_email_field_must_be_unique(): void
    {
        // Prepare
        User::factory()->create(['email' => 'unique@example.com']);
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        User::factory()->create(['email' => 'unique@example.com']);
    }

    public function test_gender_field_accepts_male_value(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['gender' => 'male']);
        // Assert
        $this->assertEquals('male', $user->gender);
    }

    public function test_gender_field_accepts_female_value(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['gender' => 'female']);
        // Assert
        $this->assertEquals('female', $user->gender);
    }

    public function test_gender_field_accepts_other_value(): void
    {
        // Prepare & Execute
        $user = User::factory()->create(['gender' => 'other']);
        // Assert
        $this->assertEquals('other', $user->gender);
    }

    public function test_gender_field_only_accepts_valid_enum_values(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        User::factory()->create(['gender' => 'invalid_gender_value']);
    }

    public function test_model_has_program_relationship(): void
    {
        // Prepare
        $program = Program::factory()->create();
        $user = User::factory()->create(['program_id' => $program->id]);
        // Execute
        $user = User::with('program')->find($user->id);
        // Assert
        $this->assertTrue($user->relationLoaded('program'));
        $this->assertNotNull($user->program);
        $this->assertEquals($program->id, $user->program->id);
    }

    public function test_model_has_program_relationship_correctly_formed(): void
    {
        // Prepare
        $user = new User();
        // Execute
        $relation = $user->program();
        // Assert
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('program_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_model_has_events_relationship(): void
    {
        // Prepare
        $creator = User::factory()->create();
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $creator->id]);
        $user->events()->attach($event->id, ['workflow_state' => 'confirmed', 'role' => 'admin']);
        // Execute
        $user = User::with('events')->find($user->id);
        // Assert
        $this->assertTrue($user->relationLoaded('events'));
        $this->assertNotEmpty($user->events);
        $this->assertEquals($event->id, $user->events->first()->id);
    }

    public function test_model_load_correct_data_for_events_relationship(): void
    {
        // Prepare
        $creator = User::factory()->create();
        $user = User::factory()->create();
        $events = Event::factory(3)->create(['user_id' => $creator->id]);
        foreach ($events as $event) {
            $user->events()->attach($event->id, ['workflow_state' => 'confirmed', 'role' => 'attendee']);
        }
        // Execute
        $user = User::with('events')->find($user->id);
        // Assert
        $this->assertCount(3, $user->events);
        $this->assertEquals(
            $events->pluck('id')->sort()->values(),
            $user->events->pluck('id')->sort()->values()
        );
    }

    public function test_events_relationship_uses_custom_pivot(): void
    {
        // Prepare
        $creator = User::factory()->create();
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $creator->id]);
        $user->events()->attach($event->id, ['workflow_state' => 'confirmed', 'role' => 'admin']);
        // Execute
        $user = User::with('events')->find($user->id);
        $pivot = $user->events->first()->pivot;
        // Assert
        $this->assertInstanceOf(EventEnroll::class, $pivot);
        $this->assertEquals('confirmed', $pivot->workflow_state);
        $this->assertEquals('admin', $pivot->role);
    }

    public function test_events_relationship_excludes_soft_deleted_pivots(): void
    {
        // Prepare
        $creator = User::factory()->create();
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $creator->id]);
        $user->events()->attach($event->id, ['workflow_state' => 'confirmed', 'role' => 'attendee']);
        $pivot = EventEnroll::where('user_id', $user->id)->where('event_id', $event->id)->first();
        $pivot->delete();
        // Execute
        $user = User::with('events')->find($user->id);
        // Assert
        $this->assertEmpty($user->events);
    }

    public function test_model_has_events_relationship_correctly_formed(): void
    {
        // Prepare
        $user = new User();
        // Execute
        $relation = $user->events();
        // Assert
        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertEquals('event_enrolls', $relation->getTable());
        $this->assertEquals('user_id', $relation->getForeignPivotKeyName());
        $this->assertEquals('event_id', $relation->getRelatedPivotKeyName());
        $this->assertEquals(Event::class, get_class($relation->getRelated()));
        $this->assertEquals(EventEnroll::class, $relation->getPivotClass());
        $this->assertContains('workflow_state', $relation->getPivotColumns());
        $this->assertContains('role', $relation->getPivotColumns());
    }

    public function test_events_pivot_implements_soft_deletes(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $user->events()->attach($event->id, ['workflow_state' => 'confirmed', 'role' => 'attendee']);
        $pivot = EventEnroll::where('user_id', $user->id)->where('event_id', $event->id)->first();
        // Execute
        $pivot->delete();
        // Assert
        $this->assertSoftDeleted('event_enrolls', ['user_id' => $user->id, 'event_id' => $event->id]);
        $this->assertNull(EventEnroll::where('user_id', $user->id)->where('event_id', $event->id)->first());
        $this->assertNotNull(EventEnroll::withTrashed()->where('user_id', $user->id)->where('event_id', $event->id)->first());
        $this->assertNotNull(EventEnroll::withTrashed()->where('user_id', $user->id)->where('event_id', $event->id)->first());
    }

    public function test_model_has_sent_contact_requests_relationship(): void
    {
        // Prepare
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $contact_request = ContactRequest::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);
        // Execute
        $sender = User::with('sentContactRequests')->find($sender->id);
        // Assert
        $this->assertTrue($sender->relationLoaded('sentContactRequests'));
        $this->assertNotEmpty($sender->sentContactRequests);
        $this->assertEquals($contact_request->id, $sender->sentContactRequests->first()->id);
    }

    public function test_model_load_correct_data_for_sent_contact_requests_relationship(): void
    {
        // Prepare
        $sender = User::factory()->create();
        $receivers = User::factory(3)->create();
        $contact_requests = collect();
        foreach ($receivers as $receiver) {
            $contact_requests->push(ContactRequest::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]));
        }
        // Execute
        $sender = User::with('sentContactRequests')->find($sender->id);
        // Assert
        $this->assertCount(3, $sender->sentContactRequests);
        $this->assertEquals($contact_requests->pluck('id')->sort()->values(), $sender->sentContactRequests->pluck('id')->sort()->values());
    }

    public function test_model_has_sent_contact_requests_relationship_correctly_formed(): void
    {
        // Prepare
        $user = new User();
        // Execute
        $relation = $user->sentContactRequests();
        // Assert
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('sender_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getLocalKeyName());
    }

    public function test_model_has_received_contact_requests_relationship(): void
    {
        // Prepare
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $contact_request = ContactRequest::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);
        // Execute
        $receiver = User::with('receivedContactRequests')->find($receiver->id);
        // Assert
        $this->assertTrue($receiver->relationLoaded('receivedContactRequests'));
        $this->assertNotEmpty($receiver->receivedContactRequests);
        $this->assertEquals($contact_request->id, $receiver->receivedContactRequests->first()->id);
    }

    public function test_model_load_correct_data_for_received_contact_requests_relationship(): void
    {
        // Prepare
        $receiver = User::factory()->create();
        $senders = User::factory(3)->create();
        $contact_requests = collect();
        foreach ($senders as $sender) {
            $contact_requests->push(ContactRequest::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]));
        }
        // Execute
        $receiver = User::with('receivedContactRequests')->find($receiver->id);
        // Assert
        $this->assertCount(3, $receiver->receivedContactRequests);
        $this->assertEquals($contact_requests->pluck('id')->sort()->values(), $receiver->receivedContactRequests->pluck('id')->sort()->values());
    }

    public function test_model_has_received_contact_requests_relationship_correctly_formed(): void
    {
        // Prepare
        $user = new User();
        // Execute
        $relation = $user->receivedContactRequests();
        // Assert
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('receiver_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getLocalKeyName());
    }

    public function test_implement_soft_deletes(): void
    {
        // Prepare
        $user = User::factory()->create();
        // Execute
        $user->delete();
        // Assert
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertNull(User::find($user->id));
        $this->assertNotNull(User::withTrashed()->find($user->id));
    }

    public function test_model_is_linked_to_correct_table(): void
    {
        // Prepare
        $user = new User();
        // Execute
        $table = $user->getTable();
        // Assert
        $this->assertEquals('users', $table);
    }

    public function test_default_eager_loaded_program_and_university_relations(): void
    {
        $program = Program::factory()->hasUniversity()->create();
        $user = User::factory()->create(['program_id' => $program->id]);
        $retrieved = User::find($user->id);
        $this->assertTrue($retrieved->relationLoaded('program'));
        $this->assertTrue($retrieved->program->relationLoaded('university'));
    }

    public function test_events_pivot_uses_timestamps(): void
    {
        // Prepare
        $creator = User::factory()->create();
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $creator->id]);
        $user->events()->attach($event->id, ['workflow_state' => 'confirmed', 'role' => 'attendee']);
        // Execute & Assert
        $pivot = $user->fresh()->events->first()->pivot;
        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    public function test_user_uses_has_api_tokens_trait(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $this->assertNotNull($token->plainTextToken);
        $this->assertCount(1, $user->tokens);
    }

    public function test_user_can_retrieve_pivot_event_when_workflow_state_is_declined(): void
    {
        // Prepare
        $creator = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $creator->id]);
        $targetUser->events()->attach($event->id, ['workflow_state' => 'declined', 'role' => 'attendee']);
        // Execute
        $user = User::with('events')->find($targetUser->id);
        // Assert
        $this->assertCount(1, $user->events);
        $retrievedEvent = $user->events->first();
        $this->assertEquals($event->id, $retrievedEvent->id);
        $this->assertEquals('declined', $retrievedEvent->pivot->workflow_state);
        $this->assertEquals('attendee', $retrievedEvent->pivot->role);
    }

    public function test_user_can_retrieve_pivot_event_when_workflow_state_is_confirmed(): void
    {
        // Prepare
        $creator = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $creator->id]);
        $targetUser->events()->attach($event->id, ['workflow_state' => 'confirmed', 'role' => 'attendee']);
        // Execute
        $user = User::with('events')->find($targetUser->id);
        // Assert
        $this->assertCount(1, $user->events);
        $retrievedEvent = $user->events->first();
        $this->assertEquals($event->id, $retrievedEvent->id);
        $this->assertEquals('confirmed', $retrievedEvent->pivot->workflow_state);
        $this->assertEquals('attendee', $retrievedEvent->pivot->role);
        $this->assertTrue($retrievedEvent->pivot->isConfirmed());
    }

    public function test_user_can_retrieve_pivot_event_when_workflow_state_is_pending(): void
    {
        // Prepare
        $creator = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $creator->id]);
        $targetUser->events()->attach($event->id, ['workflow_state' => 'pending', 'role' => 'attendee']);
        // Execute
        $user = User::with('events')->find($targetUser->id);
        // Assert
        $this->assertCount(1, $user->events);
        $retrievedEvent = $user->events->first();
        $this->assertEquals($event->id, $retrievedEvent->id);
        $this->assertEquals('pending', $retrievedEvent->pivot->workflow_state);
        $this->assertEquals('attendee', $retrievedEvent->pivot->role);
    }

    public function test_events_relationship_retrieves_events_created_by_another_user_and_current_user_is_enrolled(): void
    {
        // Prepare
        $anotherUser = User::factory()->create();
        $currentUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $anotherUser->id]);
        $currentUser->events()->attach($event->id, ['workflow_state' => 'confirmed', 'role' => 'attendee']);
        // Execute
        $user = User::with('events')->find($currentUser->id);
        // Assert
        $this->assertCount(1, $user->events);
        $retrievedEvent = $user->events->first();
        $this->assertEquals($event->id, $retrievedEvent->id);
        $this->assertEquals($anotherUser->id, $retrievedEvent->user_id);
        $this->assertNotEquals($currentUser->id, $retrievedEvent->user_id);
    }

    public function test_events_relationship_retrieves_events_created_by_the_same_user(): void
    {
        // Prepare
        $currentUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $currentUser->id]);
        // Execute
        $user = User::with('events')->find($currentUser->id);
        // Assert
        $this->assertCount(1, $user->events);
        $retrievedEvent = $user->events->first();
        $this->assertEquals($event->id, $retrievedEvent->id);
        $this->assertEquals($currentUser->id, $retrievedEvent->user_id);
        $this->assertEquals('admin', $retrievedEvent->pivot->role);
        $this->assertEquals('confirmed', $retrievedEvent->pivot->workflow_state);
    }

    public function test_event_automatically_attaches_owner_as_confirmed_admin_on_creation(): void
    {
        // Prepare
        $currentUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $currentUser->id]);
        // Execute
        $user = User::with('events')->find($currentUser->id);
        // Assert
        $this->assertCount(1, $user->events);
        $retrievedEvent = $user->events->first();
        $this->assertEquals($event->id, $retrievedEvent->id);
        $this->assertEquals($currentUser->id, $retrievedEvent->user_id);
        $this->assertEquals('admin', $retrievedEvent->pivot->role);
        $this->assertEquals('confirmed', $retrievedEvent->pivot->workflow_state);
    }
}
