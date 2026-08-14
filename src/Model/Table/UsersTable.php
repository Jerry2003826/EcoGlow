<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\User;
use App\Service\Security\PasswordPolicy;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @method \App\Model\Entity\User newEmptyEntity()
 * @method \App\Model\Entity\User newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\User> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\User findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\User> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersTable extends Table
{
    /**
     * Minimum length accepted for a password chosen through a reset link.
     *
     * @var int
     */
    public const MIN_PASSWORD_LENGTH = PasswordPolicy::MIN_LENGTH;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('email');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->hasMany('UserRoles', ['foreignKey' => 'user_id']);
        $this->hasOne('Customers', ['foreignKey' => 'user_id']);
    }

    /**
     * Identity lookup for both password login and primary-key sessions.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActiveForAuthentication(SelectQuery $query): SelectQuery
    {
        return $query->where([
            'Users.status' => 'active',
            'Users.deleted IS' => null,
        ]);
    }

    /**
     * Invalidate every other session for this account.
     *
     * @param \App\Model\Entity\User $user User.
     * @return \App\Model\Entity\User
     */
    public function bumpAuthVersion(User $user): User
    {
        $user->set('auth_version', (int)($user->get('auth_version') ?: 1) + 1);

        return $this->saveOrFail($user);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email')
            ->add('email', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
            ]);

        $validator
            ->scalar('password')
            ->maxLength('password', 255)
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        return $validator;
    }

    /**
     * Validation rules for choosing a new password from a reset link.
     *
     * Only the password pair is validated: the email address is never part of
     * that form, so it must not be required (or writable) here.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationResetPassword(Validator $validator): Validator
    {
        $this->addPasswordRules($validator, 'password');

        $validator
            ->scalar('confirm_password')
            ->requirePresence('confirm_password')
            ->notEmptyString('confirm_password', __('Please confirm your new password.'))
            ->sameAs('confirm_password', 'password', __('The two passwords do not match.'));

        return $validator;
    }

    /**
     * Customer registration. Role and status are not validated here and must
     * be set() after newEntity so they cannot be mass-assigned.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationRegister(Validator $validator): Validator
    {
        $validator
            ->email('email', false, __('Please enter a valid email address.'))
            ->requirePresence('email', 'create')
            ->notEmptyString('email', __('Please enter a valid email address.'))
            ->add('email', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => __('An account with that email already exists.'),
            ]);

        $this->addPasswordRules($validator, 'password');

        $validator
            ->scalar('confirm_password')
            ->requirePresence('confirm_password', 'create')
            ->notEmptyString('confirm_password', __('Please confirm your password.'))
            ->sameAs('confirm_password', 'password', __('The two passwords do not match.'));

        return $validator;
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @param string $field Password field.
     * @return void
     */
    private function addPasswordRules(Validator $validator, string $field): void
    {
        $validator
            ->scalar($field)
            ->requirePresence($field)
            ->notEmptyString($field, __('Please enter a password.'))
            ->minLength($field, self::MIN_PASSWORD_LENGTH, __(
                'Passwords must be at least {0} characters long.',
                self::MIN_PASSWORD_LENGTH,
            ))
            ->maxLength($field, 255)
            ->add($field, 'notCommon', [
                'rule' => static fn($value): bool => !PasswordPolicy::isRejected((string)$value),
                'message' => __('Please choose a longer password that is not a commonly used phrase.'),
            ]);
    }
}
