<?php
namespace App\Controller\Reporting;

use App\Controller\AbstractController;
//use App\Entity\Customer;
use App\Entity\User;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
//use App\Form\Model\DateRange;
use App\Reporting\CustomReportFilterType;
//use App\Project\ProjectStatisticService;
use App\Reporting\CustomReportQuery;
//use App\Reporting\ProjectDateRange\ProjectDateRangeForm;
//use App\Reporting\ProjectDateRange\ProjectDateRangeFormCustom;
//use App\Reporting\ProjectDateRange\ProjectDateRangeQuery;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
//use App\Repository\ClientRepository;
//use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
//use App\Export\Spreadsheet\Writer\XlsxWriter;
//use App\Model\DailyStatistic;
//use App\Reporting\WeekByUser\WeekByUser;
//use App\Reporting\WeekByUser\WeekByUserForm;
//use Exception;
use PhpOffice\PhpSpreadsheet\Reader\Html;
//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\Routing\Attribute\Route;
//use Symfony\Component\Security\Core\Exception\AccessDeniedException;
//use Symfony\Component\Security\Http\Attribute\IsGranted;


final class CustomReportController extends AbstractController
{

    private $timesheetRepository;
    private $userRepository;

    public function __construct(
        TimesheetRepository $timesheetRepository,
        UserRepository $userRepository
    ) {
        $this->timesheetRepository = $timesheetRepository;
        $this->userRepository = $userRepository;
    }

    #[Route(path: '/reporting/reporting_general', name: 'custom_report', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        return $this->render('reporting/reporting_general.html.twig', $this->getReportData($request));
    }

    #[Route(path: '/reporting/reporting_general/export', name: 'custom_report_export', methods: ['GET', 'POST'])]
    public function export(Request $request): Response
    {
        $content = $this->renderView('reporting/reporting_general_export.html.twig', $this->getReportData($request));
        $reader = new Html();
        $spreadsheet = $reader->loadFromString($content);

        $writer = new BinaryFileResponseWriter(new XlsxWriter(), 'reporting_general');

        return $writer->getFileResponse($spreadsheet);
    }
    private function getReportData(Request $request): array
    {
        $dateFactory = $this->getDateTimeFactory();
        $user = $this->getUser();

        $defaultStart = $dateFactory->getStartOfMonth();

        $query = new CustomReportQuery($defaultStart, $user);
        $form = $this->createFormForGetRequest(CustomReportFilterType::class, $query, [
            'timezone' => $user->getTimezone()
        ]);
        $form->submit($request->query->all(), false);

        /** @var User $user */
        $month = !empty($request->query->get('month')) ? $request->query->get('month') : $defaultStart->format('Y-m-d');
        $currentUser = !empty($request->query->get('user')) ? $request->query->get('user') : $user->getId();
        $customer = $request->query->get('customer');

        $qb = $this->timesheetRepository->createQueryBuilder('t');

        // Month
        $start = new \DateTime("$month-01");
        $end = (clone $start)->modify('last day of this month 23:59:59');
        $qb->andWhere('t.begin >= :start AND t.end <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        // Current user
        $qb->andWhere('t.user = :user')
            ->setParameter('user', $currentUser);

        // Customer
        if ($customer) {
            $qb->join('t.project', 'p')
                ->andWhere('p.customer = :customer')
                ->setParameter('customer', $customer);
        }

        // Order by
        $qb->orderBy('t.begin', 'ASC');

        $timesheets = $qb->getQuery()->getResult();

        // Total hours
        $totalSeconds = array_reduce($timesheets, function ($carry, $timesheet) {
            return $carry + $timesheet->getDuration();
        }, 0);

        // Sum
        $totalHours = $totalSeconds / 3600;

        return array(
            'report_title'  => 'Relatório Geral',
            'form'          => $form->createView(),
            'timesheets'    => $timesheets,
            'totalHours'    => $totalHours,
            'export_route'  => 'custom_report_export',
        );
    }
}
