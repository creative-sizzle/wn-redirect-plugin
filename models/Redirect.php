<?php

declare(strict_types=1);

namespace CreativeSizzle\Redirect\Models;

use Carbon\CarbonInterface;
use CreativeSizzle\Redirect\Classes\OptionHelper;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Validator;
use System\Models\RequestLog;
use Winter\Storm\Argon\Argon;
use Winter\Storm\Database\Builder;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Traits\Sortable;
use Winter\Storm\Database\Traits\Validation;
use Winter\Storm\Support\Facades\Event;

/**
 * @property string $match_type
 * @property int $status_code
 * @property string $target_type
 * @property Argon|null $from_date
 * @property Argon|null $to_date
 * @property Argon|null $last_used_at
 *
 * @method static Redirect|Builder enabled()
 * @method static Redirect|Builder testLabEnabled()
 *
 * @property RequestLog|null $systemRequestLog
 */
final class Redirect extends Model
{
    use Sortable {
        Sortable::setSortableOrder as traitSetSortableOrder;
    }
    use Validation {
        Validation::makeValidator as traitMakeValidator;
    }

    // Types
    public const TYPE_EXACT = 'exact';

    public const TYPE_PLACEHOLDERS = 'placeholders';

    public const TYPE_REGEX = 'regex';

    // Target Types
    public const TARGET_TYPE_PATH_URL = 'path_or_url';

    public const TARGET_TYPE_CMS_PAGE = 'cms_page';

    public const TARGET_TYPE_STATIC_PAGE = 'static_page';

    public const TARGET_TYPE_NONE = 'none';

    // Scheme options
    public const SCHEME_HTTP = 'http';

    public const SCHEME_HTTPS = 'https';

    public const SCHEME_AUTO = 'auto';

    public static array $types = [
        self::TYPE_EXACT,
        self::TYPE_PLACEHOLDERS,
        self::TYPE_REGEX,
    ];

    public static array $targetTypes = [
        self::TARGET_TYPE_PATH_URL,
        self::TARGET_TYPE_CMS_PAGE,
        self::TARGET_TYPE_STATIC_PAGE,
        self::TARGET_TYPE_NONE,
    ];

    public static array $statusCodes = [
        301 => 'permanent',
        302 => 'temporary',
        303 => 'see_other',
        404 => 'not_found',
        410 => 'gone',
    ];

    public $table = 'creativesizzle_redirect_redirects';

    /**
     * Validation rules.
     */
    public array $rules = [
        'from_url' => 'required',
        'from_scheme' => 'in:http,https,auto',
        'to_url' => 'different:from_url|required_if:target_type,path_or_url',
        'to_scheme' => 'in:http,https,auto',
        'cms_page' => 'required_if:target_type,cms_page',
        'static_page' => 'required_if:target_type,static_page',
        'match_type' => 'required|in:exact,placeholders,regex',
        'target_type' => 'required|in:path_or_url,cms_page,static_page,none',
        'status_code' => 'required|in:301,302,303,404,410',
        'sort_order' => 'numeric',
    ];

    /**
     * Custom validation messages.
     */
    public array $customMessages = [
        'to_url.required_if' => 'creativesizzle.redirect::lang.redirect.to_url_required_if',
        'cms_page.required_if' => 'creativesizzle.redirect::lang.redirect.cms_page_required_if',
        'static_page.required_if' => 'creativesizzle.redirect::lang.redirect.static_page_required_if',
        'is_regex' => 'creativesizzle.redirect::lang.redirect.invalid_regex',
    ];

    /**
     * Custom attribute names.
     */
    public array $attributeNames = [
        'to_url' => 'creativesizzle.redirect::lang.redirect.to_url',
        'to_scheme' => 'creativesizzle.redirect::lang.redirect.to_scheme',
        'from_url' => 'creativesizzle.redirect::lang.redirect.from_url',
        'from_scheme' => 'creativesizzle.redirect::lang.redirect.to_scheme',
        'match_type' => 'creativesizzle.redirect::lang.redirect.match_type',
        'target_type' => 'creativesizzle.redirect::lang.redirect.target_type',
        'cms_page' => 'creativesizzle.redirect::lang.redirect.target_type_cms_page',
        'static_page' => 'creativesizzle.redirect::lang.redirect.target_type_static_page',
        'status_code' => 'creativesizzle.redirect::lang.redirect.status_code',
        'from_date' => 'creativesizzle.redirect::lang.scheduling.from_date',
        'to_date' => 'creativesizzle.redirect::lang.scheduling.to_date',
        'sort_order' => 'creativesizzle.redirect::lang.redirect.sort_order',
        'requirements' => 'creativesizzle.redirect::lang.redirect.requirements',
        'last_used_at' => 'creativesizzle.redirect::lang.redirect.last_used_at',
    ];

    public $jsonable = [
        'requirements',
    ];

    public $hasMany = [
        'clients' => Client::class,
        'logs' => RedirectLog::class,
    ];

    public $belongsTo = [
        'category' => Category::class,
        'systemRequestLog' => [
            RequestLog::class,
            'key' => 'id',
            'otherKey' => 'creativesizzle_redirect_redirect_id',
        ],
    ];

    protected $guarded = [];

    protected $dates = [
        'from_date',
        'to_date',
        'last_used_at',
    ];

    protected $casts = [
        'ignore_query_parameters' => 'boolean',
        'ignore_case' => 'boolean',
        'ignore_trailing_slash' => 'boolean',
        'is_enabled' => 'boolean',
        'test_lab' => 'boolean',
        'system' => 'boolean',
        'status_code' => 'integer',
        'sort_order' => 'integer',
    ];

    protected function afterSave()
    {
        Event::fire('creativesizzle.redirect.afterRedirectSave', ['redirect' => $this]);
    }

    protected static function makeValidator(
        array $data,
        array $rules,
        array $customMessages = [],
        array $attributeNames = []
    ): Validator {
        $validator = self::traitMakeValidator($data, $rules, $customMessages, $attributeNames);

        $validator->sometimes('to_url', 'required', static function (Fluent $request): bool {
            return in_array($request->get('status_code'), ['301', '302', '303'], true)
                && $request->get('target_type') === self::TARGET_TYPE_PATH_URL;
        });

        $validator->sometimes('cms_page', 'required', static function (Fluent $request): bool {
            return in_array($request->get('status_code'), ['301', '302', '303'], true)
                && $request->get('target_type') === self::TARGET_TYPE_CMS_PAGE;
        });

        $validator->sometimes('static_page', 'required', static function (Fluent $request): bool {
            return in_array($request->get('status_code'), ['301', '302', '303'], true)
                && $request->get('target_type') === self::TARGET_TYPE_STATIC_PAGE;
        });

        $validator->sometimes('from_url', 'is_regex', static function (Fluent $request): bool {
            return $request->get('match_type') === self::TYPE_REGEX;
        });

        return $validator;
    }

    public function scopeEnabled(Builder $builder): Builder
    {
        return $builder->where('is_enabled', '=', true);
    }

    public function scopeTestLabEnabled(Builder $builder): Builder
    {
        return $builder->where('test_lab', '=', true);
    }

    public function isMatchTypeExact(): bool
    {
        return $this->match_type === self::TYPE_EXACT;
    }

    public function isMatchTypePlaceholders(): bool
    {
        return $this->match_type === self::TYPE_PLACEHOLDERS;
    }

    public function isMatchTypeRegex(): bool
    {
        return $this->match_type === self::TYPE_REGEX;
    }

    public function setSortableOrder($itemIds, array $itemOrders = null): void
    {
        $itemIds = array_map(static function ($itemId) {
            return (int) $itemId;
        }, Arr::wrap($itemIds));

        Event::fire('creativesizzle.redirect.changed', ['redirectIds' => $itemIds]);

        $this->traitSetSortableOrder($itemIds, $itemOrders);
    }

    public function setFromUrlAttribute($value): void
    {
        $this->attributes['from_url'] = urldecode((string) $value);
    }

    public function getFromDateAttribute($value): ?Argon
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return Argon::parse($value);
    }

    public function getToDateAttribute($value): ?Argon
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return Argon::parse($value);
    }

    public function getMatchTypeOptions(): array
    {
        $options = [];

        foreach (self::$types as $value) {
            $options[$value] = e(trans("creativesizzle.redirect::lang.redirect.$value"));
        }

        return $options;
    }

    public function getTargetTypeOptions(): array
    {
        return OptionHelper::getTargetTypeOptions($this->status_code);
    }

    public function getCmsPageOptions(): array
    {
        return OptionHelper::getCmsPageOptions();
    }

    public function getStaticPageOptions(): array
    {
        return OptionHelper::getStaticPageOptions();
    }

    public function getCategoryOptions(): array
    {
        return OptionHelper::getCategoryOptions();
    }

    public function filterMatchTypeOptions(): array
    {
        $options = [];

        foreach (self::$types as $value) {
            $options[$value] = e(trans(sprintf('creativesizzle.redirect::lang.redirect.%s', $value)));
        }

        return $options;
    }

    public function filterTargetTypeOptions(): array
    {
        $options = [];

        foreach (self::$targetTypes as $value) {
            $options[$value] = e(trans(sprintf('creativesizzle.redirect::lang.redirect.target_type_%s', $value)));
        }

        return $options;
    }

    public function filterStatusCodeOptions(): array
    {
        $options = [];

        foreach (self::$statusCodes as $value => $message) {
            $options[$value] = e(trans(sprintf('creativesizzle.redirect::lang.redirect.%s', $message)));
        }

        return $options;
    }

    /**
     * Triggered before the model is saved, either created or updated.
     * Make sure target fields are correctly set after saving.
     *
     * @throws Exception
     */
    public function beforeSave(): void
    {
        match ($this->target_type) {
            self::TARGET_TYPE_NONE => $this->forceFill([
                'to_url' => null,
                'cms_page' => null,
                'static_page' => null,
                'to_scheme' => self::SCHEME_AUTO,
            ]),
            self::TARGET_TYPE_PATH_URL => $this->forceFill([
                'cms_page' => null,
                'static_page' => null,
            ]),
            self::TARGET_TYPE_CMS_PAGE => $this->forceFill([
                'to_url' => null,
                'static_page' => null,
            ]),
            self::TARGET_TYPE_STATIC_PAGE => $this->forceFill([
                'to_url' => null,
                'cms_page' => null,
            ]),
        };
    }

    public function isActiveOnDate(CarbonInterface $date): bool
    {
        if ($this->from_date && $this->to_date) {
            return $date->between($this->from_date, $this->to_date);
        }

        if ($this->from_date && $this->to_date === null) {
            return $date->gte($this->from_date);
        }

        if ($this->to_date && $this->from_date === null) {
            return $date->lte($this->to_date);
        }

        return true;
    }
}
