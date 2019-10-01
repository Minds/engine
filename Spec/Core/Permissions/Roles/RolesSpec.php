<?php

namespace Spec\Minds\Core\Permissions\Roles;

use Minds\Core\Permissions\Roles\Roles;
use Minds\Core\Permissions\Roles\Flags;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RolesSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Roles::class);
    }


    public function it_should_export_constants()
    {
        expect(Roles::toArray())->shouldBeArray();
        expect(Flags::toArray())->shouldBeArray();
    }

    public function it_should_haveß_admin_permissions()
    {
        $role = $this->getRole(Roles::ROLE_ADMIN);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(true);
    }

    public function it_should_have_banned_permissions()
    {
        $role = $this->getRole(Roles::ROLE_BANNED);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(false);
    }

    public function it_should_have_channel_admin_permissions()
    {
        $role = $this->getRole(Roles::ROLE_CHANNEL_ADMIN);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(false);
    }

    public function it_should_have_open_channel_non_subscriber_permissions()
    {
        $role = $this->getRole(Roles::ROLE_OPEN_CHANNEL_NON_SUBSCRIBER);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(false);
    }

    public function it_should_have_logged_out_permissions()
    {
        $role = $this->getRole(Roles::ROLE_LOGGED_OUT);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(false);
    }

    public function it_should_have_closed_channel_non_subscriber()
    {
        $role = $this->getRole(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(false);
    }

    public function it_should_have_closed_channel_subscriber_permissions()
    {
        $role = $this->getRole(Roles::ROLE_CLOSED_CHANNEL_SUBSCRIBER);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(false);
    }

    public function it_should_have_moderated_channel_non_subscriber_permissions()
    {
        $role = $this->getRole(Roles::ROLE_MODERATED_CHANNEL_NON_SUBSCRIBER);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(false);
    }

    public function it_should_have_moderated_channel_subscriber_permissions()
    {
        $role = $this->getRole(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(false);
    }

    public function it_should_have_group_owner_permissions()
    {
        $role = $this->getRole(Roles::ROLE_GROUP_OWNER);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(true);
    }

    public function it_should_have_group_admin_permissions()
    {
        $role = $this->getRole(Roles::ROLE_GROUP_ADMIN);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(true);
    }

    public function it_should_have_group_moderator_permissions()
    {
        $role = $this->getRole(Roles::ROLE_GROUP_MODERATOR);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(true);
    }

    public function it_should_have_closed_group_subscriber_permissions()
    {
        $role = $this->getRole(Roles::ROLE_CLOSED_GROUP_SUBSCRIBER);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(true);
    }

    public function it_should_have_closed_group_non_subscriber_permissions()
    {
        $role = $this->getRole(Roles::ROLE_CLOSED_GROUP_NON_SUBSCRIBER);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(false);
    }

    public function it_should_have_open_group_subscriber_permissions()
    {
        $role = $this->getRole(Roles::ROLE_OPEN_GROUP_SUBSCRIBER);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(true);
    }

    public function it_should_have_open_group_non_subscriber_permissions()
    {
        $role = $this->getRole(Roles::ROLE_OPEN_GROUP_NON_SUBSCRIBER);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(true);
    }

    public function it_should_have_entity_owner_permissions()
    {
        $role = $this->getRole(Roles::ROLE_ENTITY_OWNER);
        $role->hasPermission(Flags::FLAG_APPOINT_ADMIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_POST)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_CHANNEL)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_POST)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_APPOINT_MODERATOR)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_APPROVE_SUBSCRIBER)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_SUBSCRIBE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_VIEW)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_VOTE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_CREATE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_EDIT_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_DELETE_COMMENT)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_REMIND)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_WIRE)->shouldEqual(true);
        $role->hasPermission(Flags::FLAG_MESSAGE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_INVITE)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_CREATE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_EDIT_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_DELETE_GROUP)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN)->shouldEqual(false);
        $role->hasPermission(Flags::FLAG_JOIN_GATHERING)->shouldEqual(false);
    }
}
