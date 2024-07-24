<?php
namespace App\Controller\Reporting;

use App\Controller\AbstractController;
//use App\Entity\Customer;
use App\Entity\User;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Reporting\CustomReportFilterType;
use App\Reporting\CustomReportQuery;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Repository\CustomerRepository;
use PhpOffice\PhpSpreadsheet\Reader\Html;

final class CustomReportController extends AbstractController
{
    private TimesheetRepository $timesheetRepository;

    private UserRepository $userRepository;

    private CustomerRepository $customerRepository;

    private string $filename = 'Relatorio_Geral';

    public function __construct(
        TimesheetRepository $timesheetRepository,
        UserRepository $userRepository,
        CustomerRepository $customerRepository
    ) {
        $this->timesheetRepository = $timesheetRepository;
        $this->userRepository = $userRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param  Request  $request
     * @return Response
     */
    #[Route(path: '/reporting/reporting_general', name: 'custom_report', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        return $this->render('reporting/reporting_general.html.twig', $this->getReportData($request));
    }

    /**
     * Export data to xls
     * @param  Request  $request
     * @return Response
     */
    #[Route(path: '/reporting/reporting_general/export', name: 'custom_report_export', methods: ['GET', 'POST'])]
    public function export(Request $request): Response
    {
        $exportFileName = date('Ym') . '_' .$this->filename;
        $finUser        = $request->query->get('user');
        $findCustomer   = $request->query->get('customer');

        $content        = $this->renderView('reporting/reporting_general_export.html.twig', $this->getReportData($request));
        $reader         = new Html();
        $spreadsheet    = $reader->loadFromString($content);


        // Add name customer
        if(!empty($findCustomer) && $customer = $this->customerRepository->find($findCustomer) ){
            $exportFileName .= '_' . trim(preg_replace('#\W+#', '_', $customer->getName()), '_');
        }

        // Add name user
        if(!empty($finUser) && $user = $this->userRepository->find( $finUser )){
            $exportFileName .= '_' . trim(preg_replace('#\W+#', '_', $user->getUsername()), '_');
        }

        $writer = new BinaryFileResponseWriter(new XlsxWriter(), $exportFileName);

        return $writer->getFileResponse($spreadsheet);
    }

    /**
     * Get data
     * @param  Request  $request
     * @return array
     */
    private function getReportData(Request $request): array
    {
        $dateFactory = $this->getDateTimeFactory();
        $user = $this->getUser();

        $defaultStart = $dateFactory->getStartOfMonth();

        /** @var User $user */
        $month = !empty($request->query->get('month')) ? $request->query->get('month') : $defaultStart->format('Y-m-d');
        $currentUser = $request->query->get('user');
        $customer = $request->query->get('customer');

        $query = new CustomReportQuery($defaultStart, $user);
        $form = $this->createFormForGetRequest(CustomReportFilterType::class, $query, [
            'timezone' => $user->getTimezone()
        ]);
        $form->get('user')->setData(null);
        $form->submit($request->query->all(), false);

        $qb = $this->timesheetRepository->createQueryBuilder('t')
            ->select('c.name AS customerName, t')
            ->join('t.project', 'p')
            ->join('p.customer', 'c');

        // Month
        $start = new \DateTime("$month-01");
        $end = (clone $start)->modify('last day of this month 23:59:59');
        $qb->andWhere('t.begin >= :start AND t.end <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        // Current user
        if($currentUser){
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $currentUser);
        }

        // Customer
        if ($customer) {
            $qb->andWhere('p.customer = :customer')
                ->setParameter('customer', $customer);
        }

        // Group by customer and order by date
        $qb->groupBy('c.id, t.id')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('t.begin', 'ASC');

        // Result
        $results = $qb->getQuery()->getResult();
        $timesheets = array_map(fn($result) => $result[0], $results);

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
