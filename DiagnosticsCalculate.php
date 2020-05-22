<?php
use function array_diff;
use modules\diagnostics\models\DiagnosticsCategory;
use yii\helpers\ArrayHelper;
use function is_nan;
use function round;

/**
 * Класс расчета самодиагностики
 *
 * @package frontend\modules\diagnostics\components
 */
class DiagnosticsCalculate
{
    /**
     * @var array
     */
    private $answers = [];

    /**
     * Таблица выявленых реакций самодиагностики
     */
    public static function getReactionTable(): array
    {
        $table = [
            ['title' => 'Агрессия против других', 'category' => [1 => [16, 37], 2 => [52, 58, 84, 85], 3 => [131, 132, 134], 4 => [146, 147, 148, 174, 175, 178]]],
            ['title' => 'Агрессия против себя', 'category' => [1 => [], 2 => [51, 54, 66, 73], 3 => [113, 114, 115, 141, 144, 161, 116, 117, 121], 4 => [162, 170, 172]]],
            ['title' => 'Обесценивание объекта', 'category' => [1 => [7, 9, 11, 33, 36, 38, 39], 2 => [], 3 => [], 4 => []]],
            ['title' => 'Рационализация обстоятельствами', 'category' => [1 => [8, 22, 23, 34, 35, 42], 2 => [54, 71, 88], 3 => [104, 107, 108, 109, 130, 133], 4 => []]],
            ['title' => 'Проекция на других', 'category' => [1 => [35], 2 => [59, 60, 70, 82], 3 => [104], 4 => [165, 172, 173]]],
            ['title' => 'Защита от чувства вины', 'category' => [1 => [14, 15, 16, 19, 20], 2 => [75, 76, 77, 78], 3 => [101, 102, 107, 109, 119, 124, 126, 135, 136], 4 => [181]]],
            ['title' => 'Защита от чувства стыда', 'category' => [1 => [1, 2], 2 => [53, 55, 56, 67, 69, 70, 83, 86, 87], 3 => [101, 110], 4 => []]],
            ['title' => 'Защита от страха неудачи', 'category' => [1 => [8, 21, 22, 23, 29, 30, 31, 33, 34, 35, 36, 38, 42], 2 => [81], 3 => [118], 4 => []]],
            ['title' => 'Защита от зависти, гордости', 'category' => [1 => [1, 12, 13, 17, 21], 2 => [], 3 => [], 4 => [158]]],
            ['title' => 'Защита от обиды ', 'category' => [1 => [], 2 => [77, 78], 3 => [], 4 => [142, 143, 146, 147, 149, 150, 151, 152, 154, 155, 156, 158, 159, 164, 169, 172, 173, 174, 176, 182]]],
            ['title' => 'Уход из ситуации', 'category' => [1 => [12, 13, 38, 39, 40, 41], 2 => [55, 57, 62, 63, 65, 67, 74, 88], 3 => [101, 103, 112, 118, 123, 125], 4 => [160, 162, 163, 164, 169, 179, 180]]],
            ['title' => 'Самоуничижение', 'category' => [1 => [3, 4, 6, 21, 24, 26, 27], 2 => [51, 64, 66, 73], 3 => [116, 119, 120, 121, 135, 136, 137], 4 => [141, 144, 145]]],
            ['title' => 'Возбуждение вины в других', 'category' => [1 => [], 2 => [], 3 => [], 4 => [155, 157, 161, 171, 172, 173]]],
            ['title' => 'Эфективное мышление Оп(эф) приводящее к расслаблению', 'category' => [1 => [5, 9, 10, 18, 28, 31, 32], 2 => [56, 61, 62, 69, 72, 79, 80, 83, 85, 87], 3 => [106, 107, 110, 111, 122, 124, 126, 127, 128, 129, 130, 133, 134, 136], 4 => [149, 150, 176, 177]]],
            ['title' => 'Несоответствие поведения других', 'category' => [1 => [25], 2 => [], 3 => [105, 106], 4 => [151, 152, 153, 165, 166, 167, 168]]],
            ['title' => 'Апеллирующее мышление', 'category' => [1 => [], 2 => [68, 77, 78], 3 => [68, 77, 78], 4 => [142, 143, 154, 155, 156, 174, 175, 181, 182, 183]]],
        ];
        return $table;
    }

    public function __construct($answers = [])
    {
        $this->answers = $answers;
    }

    /**
     * Итоговый расчет, выличины персонального психоэмоционального напряжения
     *
     *
     * @return float
     */
    public function getGeneralRatioEmotionalStress(): float
    {
        $result = round($this->getSummaryRatioEmotionalStress() * $this->getSummaryReactionCount(), 0);
        return (float)(is_nan($result) ? 0 : $result);
    }


    /**
     * Расчет обобщеного коэффициента психоэмоционального напряжения
     *
     *
     * @return float
     */
    public function getSummaryRatioEmotionalStress(): float
    {
        $categoryArr = DiagnosticsCategory::getCategoryData();
        $total = 0;

        for ($i = 0; $i < count($categoryArr); $i++) {
            $category = $categoryArr[$i];
            $k = $this->getStressFactor($category);
            $DQ = $this->getProportionOfNumberOfReaction($category, $categoryArr);
            $total += ($k * $DQ);
        }
        return round(($total / 100), 2);
    }

    /**
     * Суммирование общего количества выявленых реакций
     *
     * @return float
     */
    public function getSummaryReactionCount(): float
    {
        $categoryArr = DiagnosticsCategory::getCategoryData();
        $total = 0;

        for ($i = 0; $i < count($categoryArr); $i++) {
            $category = $categoryArr[$i];
            $total += $this->getReactionCount($category);
        }
        return $total;
    }

    /**
     * Вычисление коэфициента напряжения
     *
     * @param  DiagnosticsCategory $category Категория
     *
     * @return float
     */
    public function getStressFactor(DiagnosticsCategory $category): float
    {
        $q = $this->getReactionCount($category);
        $qEff = $this->getEffectiveReactionCount($category);

        $result = $q > 0 ? round((float)(1 - ($qEff / $q)), 2) : 0;
        return (float)(is_nan($result) ? 0 : $result);
    }

    /**
     * Количество реакций пользователя
     *
     * @param DiagnosticsCategory $category
     *
     * @return int
     */
    public function getReactionCount(DiagnosticsCategory $category): int
    {
        $userAnswers = ArrayHelper::getColumn($this->answers, 'question_id');

        $categoryQuestions = ArrayHelper::getColumn($category->question, 'id');
        return count(array_intersect($userAnswers, $categoryQuestions));
    }

    /**
     * Количество эффективных реакций пользователя
     *
     * @param DiagnosticsCategory $category
     *
     * @return int
     */
    public function getEffectiveReactionCount(DiagnosticsCategory $category): int
    {
        $userAnswers = ArrayHelper::getColumn($this->answers, 'question_id');
        $categoryQuestionsEff = ArrayHelper::getValue(self::getReactionTable(), [13, 'category', $category->id]);

        return count(array_intersect($userAnswers, $categoryQuestionsEff));
    }

    /**
     * @param DiagnosticsCategory $category
     *
     * @return array
     */
    public function getUserCategoryAnswers(DiagnosticsCategory $category): array
    {
        $userAnswers = ArrayHelper::getColumn($this->answers, 'question_id');
        $categoryQuestions = ArrayHelper::getColumn($category->question, 'id');

        return array_intersect($userAnswers,$categoryQuestions);
    }

    /**
     * Расчитываем долю количества реакций рассогласования для категории
     *
     * @param DiagnosticsCategory $category
     *
     * @param array               $categoryArr Массив категорий
     *
     * @return float
     */
    public function getProportionOfNumberOfReaction(DiagnosticsCategory $category, array $categoryArr): float
    {
        $qSum = 0;
        for ($i = 0; $i < count($categoryArr); $i++) {
            $qSum += $this->getReactionCount($categoryArr[$i]);
        }
        $q = $this->getReactionCount($category);

        return $qSum > 0 ? (float)(($q * 100) / $qSum) : 0;
    }

    /**
     * @param float $stressFactorAverage
     *
     * @return string
     */
    public static function getStressFactorAverageDesc(float $stressFactorAverage): string
    {
        if ($stressFactorAverage == 0) {
            return '<strong>Идеальное состояние мышления</strong> и в полной гармонии с "телом" (психоэмоциональное напряжение отсутствует)';
        } elseif (0 < $stressFactorAverage && $stressFactorAverage < 0.5) {
            return '<strong>Удовлетворительное состояние мышления (психики)</strong>, в т.ч. для нормы жизненных физиологических процессов, но требует повышенного к себе внимания';
        } elseif (0.5 <= $stressFactorAverage && $stressFactorAverage < 1) {
            return '<strong>Угрожающее состояние мышления (психики)</strong>, патологически влияющее на жизненные физиологические процессы организма';
        } elseif ($stressFactorAverage == 1) {
            return '<strong>Крайне критическое состояние мышления</strong>, максимально опасное для протекания жизненных физиологических процессов организма';
        }
    }

    /**
     * Статистические данные
     *
     * @param $category
     *
     * @return array
     */
    public function getStatData(DiagnosticsCategory $category): array
    {
        $reactionCount = $this->getReactionCount($category);
        $stressFactor = $this->getStressFactor($category);
        $effectiveReactionCount = $this->getEffectiveReactionCount($category);
        $f = round($reactionCount * $stressFactor,2);
        $dSf = round($stressFactor * 100 / 1, 2);
        $userCategoryAnswers = $this->getUserCategoryAnswers($category);

        return [
            $reactionCount,
            $stressFactor,
            $effectiveReactionCount,
            $f,
            $dSf,
            $userCategoryAnswers,
        ];
    }
}
